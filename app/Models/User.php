<?php

namespace App\Models;

use App\Services\PerimetreVisibilite;
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
     * Identifiants des structures dont l'utilisateur gère les agents et les projets :
     * toutes pour la Direction, celles de sa branche de l'organigramme pour un chef,
     * aucune sinon.
     */
    public function structuresGerees(): array
    {
        if (! $this->hasAnyRole([self::ROLE_DIRECTION, self::ROLE_CHEF])) {
            return [];
        }

        $perimetre = $this->perimetre();

        return $perimetre->estGlobal()
            ? Structure::query()->pluck('id')->all()
            : $perimetre->structureIds();
    }

    private ?PerimetreVisibilite $perimetreCalcule = null;

    /**
     * Ce que le compte peut voir : sa fiche et la branche de l'organigramme sous lui
     * (tout pour l'administrateur). Calculé une fois par instance.
     */
    public function perimetre(): PerimetreVisibilite
    {
        return $this->perimetreCalcule ??= PerimetreVisibilite::pour($this);
    }

    /** Vrai si l'utilisateur est affecté au projet via sa fiche agent. */
    public function estAffecteAuProjet(Projet $projet): bool
    {
        return $this->agent_id !== null
            && $projet->agents()->where('agents.id', $this->agent_id)->exists();
    }
}
