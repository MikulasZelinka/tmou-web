<?php declare(strict_types=1);
namespace InstruktoriBrno\TMOU\Services\Teams;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use InstruktoriBrno\TMOU\Enums\GameStatus;
use InstruktoriBrno\TMOU\Model\Event;
use InstruktoriBrno\TMOU\Model\Team;

class FindTeamsInEventService
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ObjectRepository|EntityRepository */
    private $teamRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->teamRepository = $this->entityManager->getRepository(Team::class);
    }

    /**
     * Returns all teams registered on given event
     *
     * @param Event $event
     * @return Team[]
     */
    public function findRegisteredTeams(Event $event): array
    {
        return $this->teamRepository->findBy(['event' => $event], ['name' => 'ASC']);
    }

    /**
     * Returns all teams qualified on given event
     *
     * @param Event $event
     * @return Team[]
     */
    public function findQualifiedTeams(Event $event): array
    {
        return $this->teamRepository->findBy(['event' => $event, 'gameStatus' => GameStatus::QUALIFIED()], ['name' => 'ASC']);
    }

    /**
     * Returns all teams qualified on given event
     *
     * @param Event $event
     * @return Team[]
     */
    public function findPlayingTeams(Event $event): array
    {
        return $this->teamRepository->findBy(['event' => $event, 'gameStatus' => GameStatus::PLAYING()], ['name' => 'ASC']);
    }
}
