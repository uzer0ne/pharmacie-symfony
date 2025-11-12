<?php

namespace App\Tests\Controller;

use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PatientControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $patientRepository;
    private string $path = '/patient/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->patientRepository = $this->manager->getRepository(Patient::class);

        foreach ($this->patientRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Patient index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'patient[nom_patient]' => 'Testing',
            'patient[prenom_patient]' => 'Testing',
            'patient[adresse_patient]' => 'Testing',
            'patient[date_naissance]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->patientRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Patient();
        $fixture->setNom_patient('My Title');
        $fixture->setPrenom_patient('My Title');
        $fixture->setAdresse_patient('My Title');
        $fixture->setDate_naissance('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Patient');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Patient();
        $fixture->setNom_patient('Value');
        $fixture->setPrenom_patient('Value');
        $fixture->setAdresse_patient('Value');
        $fixture->setDate_naissance('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'patient[nom_patient]' => 'Something New',
            'patient[prenom_patient]' => 'Something New',
            'patient[adresse_patient]' => 'Something New',
            'patient[date_naissance]' => 'Something New',
        ]);

        self::assertResponseRedirects('/patient/');

        $fixture = $this->patientRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getNom_patient());
        self::assertSame('Something New', $fixture[0]->getPrenom_patient());
        self::assertSame('Something New', $fixture[0]->getAdresse_patient());
        self::assertSame('Something New', $fixture[0]->getDate_naissance());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Patient();
        $fixture->setNom_patient('Value');
        $fixture->setPrenom_patient('Value');
        $fixture->setAdresse_patient('Value');
        $fixture->setDate_naissance('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/patient/');
        self::assertSame(0, $this->patientRepository->count([]));
    }
}
