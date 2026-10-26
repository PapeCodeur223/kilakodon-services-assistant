<?php

namespace App\Domain\Platform\Enums;

enum AuditAction: string
{
    case Login = 'login';
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case RoleChanged = 'role_changed';
    case SettingChanged = 'setting_changed';
    case Published = 'published';
    case ContactMerged = 'contact_merged';
    case Exported = 'exported';
    case Anonymized = 'anonymized';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Connexion',
            self::Created => 'Création',
            self::Updated => 'Modification',
            self::Deleted => 'Suppression',
            self::RoleChanged => 'Changement de rôle',
            self::SettingChanged => 'Paramètre modifié',
            self::Published => 'Publication',
            self::ContactMerged => 'Fusion de contacts',
            self::Exported => 'Export',
            self::Anonymized => 'Anonymisation',
        };
    }
}
