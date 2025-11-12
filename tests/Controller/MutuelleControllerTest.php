<?php

namespace App\Tests\Controller;

use App\Entity\Mutuelle;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MutuelleControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $mutuelleRepository;
    private string $path = '/mutuelle/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->mutuelleRepository = $this->manager->getRepository(Mutuelle::class);

        foreach ($this->mutuelleRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Mutuelle index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'mutuelle[nom_mutuelle]' => 'Testing',
            'mutuelle[contact_mutuelle]' => 'Testing',
            'mutuelle[taux_remboursement]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->mutuelleRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Mutuelle();
        $fixture->setNom_mutuelle('My Title');
        $fixture->setContact_mutuelle('My Title');
        $fixture->setTaux_remboursement('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Mutuelle');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Mutuelle();
        $fixture->setNom_mutuelle('Value');
        $fixture->setContact_mutuelle('Value');
        $fixture->setTaux_remboursement('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'mutuelle[nom_mutuelle]' => 'Something New',
            'mutuelle[contact_mutuelle]' => 'Something New',
            'mutuelle[taux_remboursement]' => 'Something New',
        ]);

        self::assertResponseRedirects('/mutuelle/');

        $fixture = $this->mutuelleRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getNom_mutuelle());
        self::assertSame('Something New', $fixture[0]->getContact_mutuelle());
        self::assertSame('Something New', $fixture[0]->getTaux_remboursement());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Mutuelle();
        $fixture->setNom_mutuelle('Value');
        $fixture->setContact_mutuelle('Value');
        $fixture->setTaux_remboursement('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/mutuelle/');
        self::assertSame(0, $this->mutuelleRepository->count([]));
    }
}
