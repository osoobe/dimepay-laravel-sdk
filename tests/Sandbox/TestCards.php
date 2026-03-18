<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Tests\Sandbox;

class TestCards
{
    public const VISA = [
        'brand'           => 'Visa',
        'number'          => '4111111111111111',
        'expiration_date' => '12/2025',
        'cvv'             => '123',
    ];

    public const MASTERCARD = [
        'brand'           => 'Mastercard',
        'number'          => '5555555555554444',
        'expiration_date' => '02/2026',
        'cvv'             => '265',
    ];

    public const AMEX = [
        'brand'           => 'American Express',
        'number'          => '378282246310005',
        'expiration_date' => '03/2026',
        'cvv'             => '7890',
    ];

    public const CARTES_BANCAIRES = [
        'brand'           => 'Cartes Bancaires',
        'number'          => '4360000001000005',
        'expiration_date' => '04/2040',
        'cvv'             => '123',
    ];

    public const CARNET = [
        'brand'           => 'Carnet',
        'number'          => '5062210000000009',
        'expiration_date' => '04/2024',
        'cvv'             => '123',
    ];

    public const UNION_PAY = [
        'brand'           => 'China UnionPay',
        'number'          => '6279886248094966',
        'expiration_date' => '04/2040',
        'cvv'             => '123',
    ];

    public const DINERS_CLUB = [
        'brand'           => 'Diners Club',
        'number'          => '30569309025904',
        'expiration_date' => '04/2040',
        'cvv'             => '123',
    ];

    public const DISCOVER = [
        'brand'           => 'Discover',
        'number'          => '6445646445644564',
        'expiration_date' => '04/2040',
        'cvv'             => '123',
    ];

    public const JCB = [
        'brand'           => 'JCB',
        'number'          => '3530111333300000',
        'expiration_date' => '04/2040',
        'cvv'             => '123',
    ];

    public const MAESTRO = [
        'brand'           => 'Maestro',
        'number'          => '6759649826438453',
        'expiration_date' => '04/2040',
        'cvv'             => '123',
    ];

    public const MADA = [
        'brand'           => 'Mada',
        'number'          => '4464040000000007',
        'expiration_date' => '04/2040',
        'cvv'             => '123',
    ];

    public const ELO = [
        'brand'           => 'ELO',
        'number'          => '4514160000000003',
        'expiration_date' => '04/2040',
        'cvv'             => '123',
    ];

    public const JCREW = [
        'brand'           => 'JCrew',
        'number'          => '5159971500000005',
        'expiration_date' => '04/2040',
        'cvv'             => '123',
    ];

    public const EFTPOS = [
        'brand'           => 'EFTPOS',
        'number'          => '4017950000000009',
        'expiration_date' => '04/2040',
        'cvv'             => '123',
    ];

    public const MEEZA = [
        'brand'           => 'Meeza',
        'number'          => '5078083000000002',
        'expiration_date' => '04/2040',
        'cvv'             => '123',
    ];

    /**
     * All available test cards.
     */
    public static function all(): array
    {
        return [
            'visa'             => self::VISA,
            'mastercard'       => self::MASTERCARD,
            'amex'             => self::AMEX,
            'cartes_bancaires' => self::CARTES_BANCAIRES,
            'carnet'           => self::CARNET,
            'union_pay'        => self::UNION_PAY,
            'diners_club'      => self::DINERS_CLUB,
            'discover'         => self::DISCOVER,
            'jcb'              => self::JCB,
            'maestro'          => self::MAESTRO,
            'mada'             => self::MADA,
            'elo'              => self::ELO,
            'jcrew'            => self::JCREW,
            'eftpos'           => self::EFTPOS,
            'meeza'            => self::MEEZA,
        ];
    }

    /**
     * Get a random test card.
     */
    public static function random(): array
    {
        $cards = self::all();
        return $cards[array_rand($cards)];
    }

    /**
     * Get a specific test card by brand key.
     * e.g. TestCards::get('visa')
     */
    public static function get(string $brand): array
    {
        return self::all()[$brand] ?? self::VISA;
    }
}
