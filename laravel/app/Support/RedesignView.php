<?php

namespace App\Support;

/**
 * Chooses between the redesigned template and the one it replaces.
 *
 * Both sets stay in the repository. A controller asks for a logical name and
 * gets `site.redesign.<name>` while config('redesign.enabled') is true, and
 * `site.<name>` the moment it is switched off — so a rollback is one env var,
 * not a revert.
 *
 * If a redesigned file is missing for some view, the old one is used for that
 * page alone. That is what lets the redesign land page by page instead of as
 * one all-or-nothing jump.
 */
class RedesignView
{
    public static function pick(string $name): string
    {
        $new = 'site.redesign.' . $name;

        if (Redesign::enabled() && view()->exists($new)) {
            return $new;
        }

        return 'site.' . $name;
    }
}
