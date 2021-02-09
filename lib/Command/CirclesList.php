<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Circles\Command;

use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Traits\TArrayTools;
use OC\Core\Command\Base;
use OC\User\NoUserException;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ModelManager;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\RemoteService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class CirclesList
 *
 * @package OCA\Circles\Command
 */
class CirclesList extends Base {


	use TArrayTools;


	/** @var ModelManager */
	private $modelManager;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var RemoteService */
	private $remoteService;

	/** @var CircleService */
	private $circleService;

	/** @var ConfigService */
	private $configService;


	/** @var InputInterface */
	private $input;


	/**
	 * CirclesList constructor.
	 *
	 * @param ModelManager $modelManager
	 * @param FederatedUserService $federatedUserService
	 * @param RemoteService $remoteService
	 * @param CircleService $circleService
	 * @param ConfigService $configService
	 */
	public function __construct(
		ModelManager $modelManager, FederatedUserService $federatedUserService, RemoteService $remoteService,
		CircleService $circleService, ConfigService $configService
	) {
		parent::__construct();
		$this->modelManager = $modelManager;
		$this->federatedUserService = $federatedUserService;
		$this->remoteService = $remoteService;
		$this->circleService = $circleService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:list')
			 ->setDescription('listing current circles')
			 ->addOption('instance', '', InputOption::VALUE_REQUIRED, 'Instance of the circle', '')
			 ->addOption('initiator', '', InputOption::VALUE_REQUIRED, 'set an initiator to the request', '')
			 ->addOption('member', '', InputOption::VALUE_REQUIRED, 'search for member', '')
			 ->addOption('def', '', InputOption::VALUE_NONE, 'display complete circle configuration')
			 ->addOption('all', '', InputOption::VALUE_NONE, 'display also hidden Circles')
			 ->addOption('json', '', InputOption::VALUE_NONE, 'returns result as JSON');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws CircleNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws MemberNotFoundException
	 * @throws NoUserException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->input = $input;
		$member = $input->getOption('member');
		$instance = $input->getOption('instance');
		$initiator = $input->getOption('initiator');

		$this->federatedUserService->commandLineInitiator($initiator, '', true);

		$filter = null;
		if ($member !== '') {
			$filter = $this->federatedUserService->createFilterMember($member);
		}

		if ($instance !== '') {
			if ($member !== '') {
				// TODO make --member working with --instance
				throw new MemberNotFoundException('--member cannot be used with --instance');
			}

			$circles = $this->remoteService->getCirclesFromInstance($instance);
		} else {
			$circles = $this->getCircles($filter, $input->getOption('all'));
		}

		if ($input->getOption('json')) {
			echo json_encode($circles, JSON_PRETTY_PRINT) . "\n";

			return 0;
		}

		$this->displayCircles($circles);

		return 0;
	}


	/**
	 * @param Circle[] $circles
	 */
	private function displayCircles(array $circles): void {
		$output = new ConsoleOutput();
		$output = $output->section();
		$table = new Table($output);
		$table->setHeaders(['ID', 'Name', 'Type', 'Owner', 'Instance', 'Limit', 'Description']);
		$table->render();

		$local = $this->configService->getLocalInstance();
		$display = ($this->input->getOption('def') ? ModelManager::TYPES_LONG : ModelManager::TYPES_SHORT);
		foreach ($circles as $circle) {
			$owner = $circle->getOwner();
			$table->appendRow(
				[
					$circle->getId(),
					$circle->getName(),
					json_encode($this->modelManager->getCircleTypes($circle, $display)),
					$owner->getUserId(),
					($owner->getInstance() === $local) ? '' : $owner->getInstance(),
					$this->getInt('members_limit', $circle->getSettings(), -1),
					substr($circle->getDescription(), 0, 30)
				]
			);
		}
	}

	/**
	 * @param Member|null $filter
	 * @param bool $all
	 *
	 * @return Circle[]
	 * @throws InitiatorNotFoundException
	 */
	private function getCircles(?Member $filter, bool $all = false): array {
		$circles = $this->circleService->getCircles($filter);

		if ($all) {
			return $circles;
		}

		$filtered = [];
		foreach ($circles as $circle) {
			if (!$circle->isConfig(Circle::CFG_SINGLE)
				&& !$circle->isConfig(Circle::CFG_HIDDEN)
				&& !$circle->isConfig(Circle::CFG_BACKEND)) {
				$filtered[] = $circle;
			}
		}

		return $filtered;
	}

}

