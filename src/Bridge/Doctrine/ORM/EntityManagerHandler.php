<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Doctrine\ORM;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class EntityManagerHandler implements RequestHandlerInterface
{
    private $decorated;
    private $connection;
    private $entityManager;
    private $managerRegistry;

    public function __construct(
        RequestHandlerInterface $decorated,
        EntityManagerInterface $entityManager,
        ManagerRegistry $managerRegistry,
    ) {
        $this->decorated = $decorated;
        $this->entityManager = $entityManager;
        $this->connection = $entityManager->getConnection();
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, Response $response): void
    {
        try {
            $this->connection->executeQuery($this->connection->getDatabasePlatform()->getDummySelectSQL());
        } catch (DBALException $e) {
            $this->connection->close();
            $this->connection->connect();
        }

        if (!$this->entityManager->isOpen()) {
            $this->managerRegistry->resetManager();
        }

        $this->decorated->handle($request, $response);

        $this->entityManager->clear();
    }
}
