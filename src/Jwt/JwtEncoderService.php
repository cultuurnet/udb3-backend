<?php

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Clock\Clock;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token as Jwt;
use ValueObjects\Number\Integer as IntegerLiteral;

class JwtEncoderService implements JwtEncoderServiceInterface
{
    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var Signer
     */
    private $signer;

    /**
     * @var Key
     */
    private $key;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var IntegerLiteral
     */
    private $exp;

    /**
     * @var IntegerLiteral
     */
    private $nbf;

    /**
     * @param Builder $builder
     * @param Signer $signer
     * @param Key $key
     * @param Clock $clock
     * @param IntegerLiteral $exp
     * @param IntegerLiteral $nbf
     */
    public function __construct(
        Builder $builder,
        Signer $signer,
        Key $key,
        Clock $clock,
        IntegerLiteral $exp,
        IntegerLiteral $nbf = null
    ) {
        $this->builder = $builder;
        $this->signer = $signer;
        $this->key = $key;
        $this->clock = $clock;
        $this->exp = $exp;
        $this->nbf = !is_null($nbf) ? $nbf : new IntegerLiteral(0);
    }

    /**
     * @param array $claims
     * @return Jwt
     */
    public function encode($claims)
    {
        $builder = clone $this->builder;

        foreach ($claims as $claim => $value) {
            $builder->set($claim, $value);
        }

        $dateTime = $this->clock->getDateTime();
        $time = $dateTime->getTimestamp();

        $jwt = $builder
            ->setIssuedAt($time)
            ->setExpiration($time + $this->exp->toNative())
            ->setNotBefore($time + $this->nbf->toNative())
            ->sign(
                $this->signer,
                $this->key
            )
            ->getToken();

        return $jwt;
    }
}
