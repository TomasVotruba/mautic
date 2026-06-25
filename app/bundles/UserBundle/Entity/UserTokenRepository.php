<?php

namespace Mautic\UserBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<UserToken>
 */
final class UserTokenRepository extends CommonRepository implements UserTokenRepositoryInterface
{
    /**
     * @param string $secret
     */
    public function isSecretUnique($secret): bool
    {
        $tokens = $this->createQueryBuilder('ut')
            ->where('ut.secret = :secret')
            ->setParameter('secret', $secret)
            ->setMaxResults(1)
            ->getQuery()->execute();

        return count($tokens) === 0;
    }

    public function verify(UserToken $token): bool
    {
        /** @var UserToken[] $userTokens */
        $userTokens = $this->createQueryBuilder('ut')
            ->where('ut.user = :user AND ut.authorizator = :authorizator AND ut.secret = :secret AND (ut.expiration IS NULL OR ut.expiration >= :now)')
            ->setParameter('user', $token->getUser())
            ->setParameter('authorizator', $token->getAuthorizator())
            ->setParameter('secret', $token->getSecret())
            ->setParameter('now', new \DateTime())
            ->setMaxResults(1)
            ->getQuery()->execute();
        $verified = (count($userTokens) !== 0);
        if ($verified === false) {
            return false;
        }
        $userToken = reset($userTokens);
        if ($userToken->isOneTimeOnly()) {
            $this->deleteEntity($userToken);
        }

        return true;
    }

    public function deleteExpired($isDryRun = false): int
    {
        $qb = $this->createQueryBuilder('ut');

        if ($isDryRun) {
            $qb->select('count(ut.id) as records');
        } else {
            $qb->delete(UserToken::class, 'ut');
        }

        return (int) $qb
            ->where('ut.expiration <= :current_datetime')
            ->setParameter('current_datetime', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
