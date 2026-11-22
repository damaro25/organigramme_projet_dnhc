<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    /** Rôles de l'application (spatie/laravel-permission). */
    public const ROLE_ADMIN = 'administrateur';

    public const ROLE_DIRECTION = 'direction';

    public const ROLE_CHEF = 'chef_structure';

    public const ROLE_CHARGE = 'charge_etudes';

    public const ROLE_CONSULTATION = 'consultation';

    protected $fillable = ['name', 'email', 'password', 'agent_id', 'actif'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('utilisateurs')
            ->logOnly(['name', 'email', 'agent_id', 'actif'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function estAdministrateur(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * Identifiants des structures que l'utilisateur gère : sa structure et
     * ses sous-structures s'il est chef, aucune sinon.
     */
    public function structuresGerees(): array
    {
        if (! $this->hasRole(self::ROLE_CHEF) || ! $this->agent?->structure) {
            return [];
        }

        return $this->agent->structure->descendantIds();
    }

    /**
     * Règle d'accès : un compte ne voit que ce qu'il peut modifier.
     *
     * - Projets : l'Administrateur et la Direction (qui valide les projets) les voient
     *   tous ; un chef, ceux de sa branche et ceux où il est affecté (il peut y cocher
     *   des étapes) ; un chargé d'études, ceux où il est affecté.
     * - Agents : l'Administrateur les voit tous ; un chef, ceux de sa branche ; un
     *   chargé d'études, sa propre fiche (pour sa photo).
     * - Le compte Consultation ne modifie rien : il n'a pas accès à l'administration.
     */
    public function voitTousLesProjets(): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_DIRECTION]);
    }

    public function voitTousLesAgents(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /** Vrai si le compte peut modifier au moins un agent (fiche, poste ou photo). */
    public function peutModifierDesAgents(): bool
    {
        return $this->voitTousLesAgents()
            || ($this->can('gerer_agents') && $this->structuresGerees() !== [])
            || ($this->can('modifier_photos') && $this->agent_id !== null);
    }

    /** Structures proposées dans les filtres ; null = toutes. */
    public function structuresVisibles(): ?array
    {
        return $this->voitTousLesProjets() ? null : $this->structuresGerees();
    }

    /** Vrai si l'utilisateur est affecté au projet via sa fiche agent. */
    public function estAffecteAuProjet(Projet $projet): bool
    {
        return $this->agent_id !== null
            && $projet->agents()->where('agents.id', $this->agent_id)->exists();
    }
}
