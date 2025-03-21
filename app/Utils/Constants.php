<?php

namespace App\Utils;

class Constants
{

    public const DEFAULT_PER_PAGE = 15;

    const ES_MONTHS = [
        'January' => "Enero",
        'February' => "Febrero",
        'March' => "Marzo",
        'April' => "Abril",
        'May' => "Mayo",
        'June' => "Junio",
        'July' => "Julio",
        'August' => "Agosto",
        'September' => "Septiembre",
        'October' => "Octubre",
        'November' => "Noviembre",
        'December' => "Diciembre",
    ];

    public const STATUS_ACTIVITY_PENDIENTE='PENDIENTE';
    public const STATUS_ACTIVITY_EN_EJECUCION='EN EJECUCION';
    public const STATUS_ACTIVITY_FINALIZADO='FINALIZADO';

    public const CONTRIBUTION_TYPE_DINERO='DINERO';
    public const CONTRIBUTION_TYPE_RECURSOS='RECURSOS';
    public const CONTRIBUTION_TYPE_SERVICIOS='SERVICIOS';
    public const CONTRIBUTION_TYPES = [
        self::CONTRIBUTION_TYPE_DINERO,
        self::CONTRIBUTION_TYPE_RECURSOS,
        self::CONTRIBUTION_TYPE_SERVICIOS
    ];
    
}
