<?php
namespace Refactory\Login\Service;

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
use TYPO3\Flow\Security\Account;
use TYPO3\Party\Domain\Model\AbstractParty;

/**
 * An AccountManagementService service
 *
 */
class AccountManagementService {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @Flow\Inject
	 * @var \Refactory\Login\Domain\Repository\ResetPasswordTokenRepository
	 */
	protected $resetPasswordTokenRepository;

	/**
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 * @Flow\Inject
	 */
	protected $hashService;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Set a new password for the given account
	 *
	 * This allows for setting a new password for an existing user account.
	 *
	 * @param Account $account
	 * @param $password
	 * @param string $passwordHashingStrategy
	 *
	 * @return boolean
	 */
	public function resetPassword(Account $account, $password, $passwordHashingStrategy = 'default') {
		$account->setCredentialsSource($this->hashService->hashPassword($password, $passwordHashingStrategy));
		$this->accountRepository->update($account);
		return TRUE;
	}

	/**
	 * @param Account $account
	 * @param \TYPO3\Flow\Mvc\ActionRequest $request
	 * @return \Refactory\Login\Domain\Model\ResetPasswordToken
	 */
	public function generateResetPasswordToken(Account $account, \TYPO3\Flow\Mvc\ActionRequest $request = NULL) {
		list($generatedToken, $salt) = explode(',', \TYPO3\Flow\Security\Cryptography\SaltedMd5HashingStrategy::generateSaltedMd5($account->getAccountIdentifier()));
		$resetPasswordToken = new \Refactory\Login\Domain\Model\ResetPasswordToken();
		$resetPasswordToken->setDate(new \DateTime());
		$resetPasswordToken->setAccount($account);
		$resetPasswordToken->setToken($generatedToken);
		$resetPasswordToken->setIp($request->getHttpRequest()->getClientIpAddress());
		$resetPasswordToken->setActive(TRUE);
		$this->resetPasswordTokenRepository->add($resetPasswordToken);
		return $resetPasswordToken;
	}

	/**
	 * @param AbstractParty $party
	 * @param \TYPO3\Flow\Mvc\ActionRequest $request
	 * @return \Refactory\Login\Domain\Model\ResetPasswordToken
	 * @throws \Exception
	 * @throws \TYPO3\Flow\Exception
	 */
	public function generateResetPasswordTokenForParty(AbstractParty $party, \TYPO3\Flow\Mvc\ActionRequest $request = NULL) {
		$accounts = $party->getAccounts();

		foreach ($accounts as $account) {
			$request->getHttpRequest()->getClientIpAddress();
			return $this->generateResetPasswordToken($account, $request);
		}
	}

	/**
	 * @param $token
	 * @return bool
	 */
	public function isTokenActive($token) {
		$isTokenActive = FALSE;
		$resetPasswordToken = $this->resetPasswordTokenRepository->findOneByToken($token);
		if ($resetPasswordToken) {
			$isTokenActive = $resetPasswordToken->isActive($this->settings['tokenExpiration']);
		}
		return $isTokenActive;
	}

	/**
	 * @param string $token
	 */
	public function deactivateToken($token) {
		$resetPasswordToken = $this->resetPasswordTokenRepository->findOneByToken($token);
		if ($resetPasswordToken) {
			$resetPasswordToken->setActive(FALSE);
			$this->resetPasswordTokenRepository->update($resetPasswordToken);
		}
	}

	/**
	 * Method to find account by given token
	 * @param string $token
	 * @return \TYPO3\Flow\Security\Account
	 */
	public function getAccountByToken($token) {
		return $this->resetPasswordTokenRepository->findOneByToken($token)->getAccount();
	}
}