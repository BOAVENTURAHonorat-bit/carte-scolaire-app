<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * DG : liste de tous les utilisateurs, avec leur rôle.
     * Un DG ne voit pas les comptes dev : seul un compte dev gère les autres dev.
     */
    public function index(): View
    {
        $users = User::when(! Auth::user()->isDev(), fn ($query) => $query->where('role', '!=', 'dev'))
            ->orderBy('name')
            ->get();

        return view('admins.index', compact('users'));
    }

    /**
     * DG : formulaire de création d'un compte (secrétaire, DG ou surveillant).
     */
    public function create(): View
    {
        return view('admins.create');
    }

    /**
     * DG : enregistrer le nouveau compte, sans se connecter à sa place.
     * Seul un compte dev peut créer un autre compte dev.
     */
    public function store(Request $request): RedirectResponse
    {
        $allowedRoles = Auth::user()->isDev() ? 'secretaire,dg,surveillant,dev' : 'secretaire,dg,surveillant';

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'in:'.$allowedRoles],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        return redirect()
            ->route('admins.create')
            ->with('status', "Le compte de {$data['name']} a été créé avec succès.");
    }

    /**
     * DG : formulaire de modification d'un utilisateur (nom, email, rôle, mot de passe).
     * Un DG n'a pas accès à la fiche d'un compte dev.
     */
    public function edit(User $user): View
    {
        $this->guardAgainstManagingDev($user);

        return view('admins.edit', compact('user'));
    }

    /**
     * DG : enregistrer les modifications d'un utilisateur.
     * Seul un compte dev peut promouvoir ou conserver quelqu'un en dev, ou modifier un compte dev existant.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->guardAgainstManagingDev($user);

        $allowedRoles = Auth::user()->isDev() ? 'secretaire,dg,surveillant,dev' : 'secretaire,dg,surveillant';

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'role' => ['required', 'in:'.$allowedRoles],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()
            ->route('admins.index')
            ->with('status', "Le compte de {$user->name} a été mis à jour.");
    }

    /**
     * Dev uniquement (pas le DG) : réinitialiser le mot de passe d'un autre admin et
     * le déconnecter immédiatement (suppression de ses sessions actives en base). Le
     * nouveau mot de passe généré s'affiche une seule fois, à transmettre à la personne.
     */
    public function resetPassword(User $user): RedirectResponse
    {
        abort_unless(Auth::user()->isDev(), 403, 'Seul un compte dev peut réinitialiser un mot de passe.');

        if ($user->id === Auth::id()) {
            return redirect()
                ->route('admins.index')
                ->withErrors(['user' => "Utilise \"Mon profil\" pour changer ton propre mot de passe."]);
        }

        $newPassword = Str::password(10);

        $user->update(['password' => Hash::make($newPassword)]);

        DB::table('sessions')->where('user_id', $user->id)->delete();

        return redirect()
            ->route('admins.index')
            ->with('status', "Mot de passe de {$user->name} réinitialisé et session(s) active(s) déconnectée(s).")
            ->with('generatedPassword', ['user' => $user->name, 'password' => $newPassword]);
    }

    /**
     * DG : supprimer un utilisateur (impossible de se supprimer soi-même).
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->guardAgainstManagingDev($user);

        if ($user->id === Auth::id()) {
            return redirect()
                ->route('admins.index')
                ->withErrors(['user' => "Tu ne peux pas supprimer ton propre compte."]);
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admins.index')
            ->with('status', "Le compte de {$name} a été supprimé.");
    }

    /**
     * Un DG n'a aucun droit sur un compte dev (voir, modifier, réinitialiser son mot de
     * passe, le supprimer) : seul un compte dev garde le contrôle sur les autres dev.
     * Vérifié ici côté serveur — jamais seulement caché dans l'interface — pour bloquer
     * aussi un accès direct par URL.
     */
    private function guardAgainstManagingDev(User $user): void
    {
        if ($user->isDev() && ! Auth::user()->isDev()) {
            abort(403, "Seul un compte dev peut gérer un autre compte dev.");
        }
    }
}
