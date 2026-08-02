<?php

namespace App\Enums;

/**
 * Capacités atomiques du contrôle d'accès (RBAC). L'autorisation de
 * l'application s'appuie sur ces permissions — jamais sur une comparaison
 * de rôle en dur — via les Gates enregistrés dans AppServiceProvider::boot().
 * La carte rôle → permissions est portée par UserRole::permissions().
 */
enum Permission: string
{
    case ManageUsers = 'users.manage';           // gestion des comptes, rôles et activations
    case ManageSettings = 'settings.manage';     // identité, marque, réglages applicatifs
    case ManageConnectors = 'connectors.manage'; // connecteurs externes (SMTP, …)
    case ViewMetrics = 'metrics.view';           // tableau de bord d'activité
    case ViewAudit = 'audit.view';               // journal d'audit (module phase 2)
    case ManageRoadmap = 'roadmap.manage';       // arbitrage des idées (statut, priorité, difficulté)
    case ManageContent = 'content.manage';       // édition des contenus du site (micro-CMS)
    case ManageMedia = 'media.manage';           // bibliothèque de médias

    /** Clé de traduction du libellé lisible (matrice RBAC des réglages). */
    public function labelKey(): string
    {
        return 'rbac.perm'.str_replace('.', '', ucwords($this->name));
    }
}
