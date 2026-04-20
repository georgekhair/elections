<?php

namespace App\Helpers;

class RoleHelper
{
    public static function config($user)
    {
        foreach ($user->getRoleNames() as $role) {
            if (config("roles.$role")) {
                return config("roles.$role");
            }
        }

        return [];
    }

    public static function canViewAllVoters($user)
    {
        return self::config($user)['view_all_voters'] ?? false;
    }

    public static function restrictToFamilies($user)
    {
        return self::config($user)['restrict_to_families'] ?? true;
    }
}
