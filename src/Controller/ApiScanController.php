<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

class ApiScanController extends AbstractController
{
    /**
     * Endpoint API public pour recevoir le scan d'une carte de mutuelle.
     */
    #[Route('/api/scan-mutuelle', name: 'api_scan_mutuelle', methods: ['POST'])]
    #[Route('/mutuelle/api/scan-mutuelle', name: 'api_scan_mutuelle_legacy', methods: ['POST'])]
    public function receiveScan(
        Request $request, 
        LoggerInterface $logger,
        \Symfony\Contracts\Cache\CacheInterface $cache
    ): JsonResponse {
        $content = $request->getContent();

        if (empty($content)) {
            return new JsonResponse(['error' => 'Le corps de la requête est vide.'], 400);
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return new JsonResponse(['error' => 'Format JSON invalide.'], 400);
        }

        if (!isset($data['code']) || trim($data['code']) === '') {
            return new JsonResponse(['error' => 'La clé "code" est manquante.'], 400);
        }

        $code = trim($data['code']);
        $logger->info('Scan Mutuelle Reçu (Brut) : {code}', ['code' => $code]);

        // Découpage de la norme AMC (ex: AMC#1#98532001#B80#571906825#AL*/)
        $parts = explode('#', $code);
        if (count($parts) >= 5 && $parts[0] === 'AMC') {
            $amcData = [
                'code_amc'        => $parts[2], // ex: 98532001
                'convention'      => $parts[3], // ex: B80
                'numero_adherent' => $parts[4], // ex: 571906825
                'timestamp'       => time()
            ];

            // On stocke le résultat dans le cache pendant 60 secondes
            $cache->delete('last_mutuelle_scan');
            $cache->get('last_mutuelle_scan', function(\Symfony\Contracts\Cache\ItemInterface $item) use ($amcData) {
                $item->expiresAfter(60);
                return $amcData;
            });
        }

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Scan reçu et mis en cache.'
        ]);
    }

    /**
     * Route appelée par le navigateur (AJAX) pour récupérer le dernier scan.
     */
    #[Route('/api/get-last-scan', name: 'api_get_last_scan', methods: ['GET'])]
    public function getLastScan(
        \Symfony\Contracts\Cache\CacheInterface $cache, 
        \App\Repository\MutuelleRepository $mutuelleRepo, 
        \App\Repository\PatientMutuelleRepository $pmRepo,
        \Doctrine\ORM\EntityManagerInterface $em
    ): JsonResponse {
        $scan = $cache->get('last_mutuelle_scan', function() {
            return null; // si expiré ou vide
        });

        if (!$scan) {
            return new JsonResponse(['status' => 'waiting']);
        }

        // On efface le cache pour ne pas le lire 2 fois
        $cache->delete('last_mutuelle_scan');

        // On cherche le nom de la mutuelle dans notre BDD
        $mutuelle = $mutuelleRepo->findOneBy(['code_amc' => $scan['code_amc']]);
        
        // Création automatique si elle n'existe pas
        if (!$mutuelle && isset($scan['code_amc'])) {
            $mutuelle = new \App\Entity\Mutuelle();
            $mutuelle->setCodeAmc($scan['code_amc']);
            $mutuelle->setNomMutuelle('Mutuelle ' . $scan['code_amc']); // Nom temporaire
            $mutuelle->setContactMutuelle('Non renseigné');
            
            $em->persist($mutuelle);
            $em->flush();
        }

        $scan['mutuelle_id'] = $mutuelle->getIdMutuelle();
        $scan['nom_mutuelle'] = $mutuelle->getNomMutuelle();
        $scan['nouvelle_mutuelle'] = true;

        // On vérifie si ce numéro d'adhérent existe déjà chez un patient
        $existing = $pmRepo->findOneBy(['numero_adherent' => $scan['numero_adherent']]);
        if ($existing && $existing->getPatient()) {
            $scan['alerte_doublon'] = 'Ce numéro d\'adhérent est déjà utilisé par le patient : ' . $existing->getPatient()->getNomPatient() . ' ' . $existing->getPatient()->getPrenomPatient();
        }

        return new JsonResponse([
            'status' => 'success',
            'data'   => $scan
        ]);
    }
}
