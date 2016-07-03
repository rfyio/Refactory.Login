<?php
namespace Refactory\Login\Helpers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Refactory.Login".       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

class SecurityHelper {

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * @var string
	 * @Flow\Inject(setting="partyRepositoryClassName", package="Refactory.Login")
	 */
	protected $partyRepositoryClassName;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @return null
	 */
	public function getCurrentUser() {
		$currentAccount = $this->getCurrentAccount();
		if ($currentAccount != NULL) {

			$partyRepository = $this->objectManager->get($this->partyRepositoryClassName);
			return $partyRepository->findOneHavingAccount($currentAccount);
		}
		return NULL;
	}

	/**
	 * @return null
	 */
	public function getCurrentAccount() {
		$tokens = $this->securityContext->getAuthenticationTokens();
		$currentUser = NULL;
		foreach ($tokens as $token) {
			if ($token->isAuthenticated()) {
				$currentUser = $token->getAccount();
				break;
			}
		}
		return $currentUser;
	}

	/**
	 * \TYPO3\Flow\Security\Account $account The account
	 */
	public function autoAuthenticate($account) {
		$authenticationTokens = $this->securityContext->getAuthenticationTokensOfType('TYPO3\Flow\Security\Authentication\Token\UsernamePassword');
		if (count($authenticationTokens) === 1) {
			$authenticationTokens[0]->setAccount($account);
			$authenticationTokens[0]->setAuthenticationStatus(\TYPO3\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);
		}
	}

}