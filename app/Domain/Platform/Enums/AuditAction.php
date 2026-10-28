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
    case StatusChanged = 'status_changed';
    case RevisionRestored = 'revision_restored';
    case AuthorizationAttested = 'authorization_attested';
    case TemplateActivated = 'template_activated';
    case TemplateDuplicated = 'template_duplicated';
    case BriefValidated = 'brief_validated';
    case BriefAccessRevoked = 'brief_access_revoked';

    public function label(): string
    {
        return match ($this) {
            self::StatusChanged => 'Changement de statut',
            self::RevisionRestored => 'Révision restaurée',
            self::AuthorizationAttested => 'Autorisation client attestée',
            self::TemplateActivated => 'Questionnaire activé',
            self::TemplateDuplicated => 'Questionnaire dupliqué',
            self::BriefValidated => 'Brief validé',
            self::BriefAccessRevoked => 'Lien de brief révoqué',
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
