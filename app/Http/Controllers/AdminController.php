<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Donation;
use App\Models\DonationArchive;
use App\Models\Setting;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'topics' => Topic::count(),
            'categories' => Category::count(),
        ];

        $goal = Setting::get('fundraising_goal', null);
        $paidTotal = Donation::where('status', 'paid')->sum('amount');
        $pendingTotal = Donation::where('status', 'pending')->sum('amount');
        $pendingCount = Donation::where('status', 'pending')->count();
        $pendingDonation = Donation::where('status', 'pending')->orderBy('created_at')->first();

        return view('admin.index', compact('stats', 'goal', 'paidTotal', 'pendingTotal', 'pendingCount', 'pendingDonation'));
    }

    public function updateGoal(Request $request)
    {
        $validated = $request->validate([
            'goal' => ['nullable', 'numeric', 'min:0'],
        ]);

        $value = $validated['goal'] !== null ? (string) $validated['goal'] : null;
        Setting::set('fundraising_goal', $value);

        return back()->with('success', 'Objectif mis à jour.');
    }

    public function resetGoal()
    {
        Setting::set('fundraising_goal', null);
        return back()->with('success', 'Objectif réinitialisé.');
    }

    public function resetPaidAmounts()
    {
        // Archiver tous les dons avant suppression
        $donations = \App\Models\Donation::all();
        foreach ($donations as $d) {
            \App\Models\DonationArchive::create([
                'original_id' => $d->id,
                'user_id' => $d->user_id,
                'amount' => $d->amount,
                'provider' => $d->provider,
                'status' => $d->status,
                'external_reference' => $d->external_reference,
                'donated_at' => $d->created_at,
            ]);
        }

        // Puis supprimer les dons originaux
        \App\Models\Donation::query()->delete();

        return back()->with('success', 'Tous les dons ont été archivés puis supprimés.');
    }

    public function categories()
    {
        $categories = Category::orderBy('ordering')->get();

        return view('admin.categories', compact('categories'));
    }

    public function donationsArchive()
    {
        $archives = DonationArchive::with('user')->latest()->paginate(50);
        return view('admin.donations-archive', compact('archives'));
    }

    public function usersList()
    {
        $superAdminId = Setting::get('super_admin_id', null);
        $users = User::orderBy('created_at', 'desc')->paginate(50);
        return view('admin.users', compact('users', 'superAdminId'));
    }

    public function makeAdmin(User $user)
    {
        $currentId = auth()->id();
        $superAdminId = Setting::get('super_admin_id', null);

        if ($currentId != $superAdminId) {
            abort(403, 'Seul l\'administrateur par défaut peut attribuer des rôles.');
        }

        $user->role = 'admin';
        $user->save();

        return back()->with('success', 'Utilisateur promu administrateur (second degré).');
    }

    public function updateRole(Request $request, User $user)
    {
        $currentId = auth()->id();
        $superAdminId = Setting::get('super_admin_id', null);

        if ($currentId != $superAdminId) {
            abort(403, 'Seul l\'administrateur par défaut peut modifier les rôles.');
        }

        if ($user->id == $superAdminId) {
            return back()->with('error', 'Impossible de modifier le rôle du super‑admin.');
        }

        $validated = $request->validate([
            'role' => ['required', 'in:member,admin'],
        ]);

        $user->role = $validated['role'];
        $user->save();

        return back()->with('success', 'Rôle utilisateur mis à jour.');
    }

    public function toggleBlock(User $user)
    {
        $current = auth()->user();
        $superAdminId = Setting::get('super_admin_id', null);

        if ($user->id == $superAdminId) {
            return back()->with('error', 'Impossible de bloquer le super‑admin.');
        }

        if (!$current || ($current->id != $superAdminId && ($current->role ?? null) !== 'admin')) {
            abort(403, 'Non autorisé.');
        }

        $user->status = ($user->status === 'blocked') ? 'active' : 'blocked';
        $user->save();

        return back()->with('success', 'Statut utilisateur mis à jour.');
    }

    public function deleteUser(User $user)
    {
        $current = auth()->user();
        $superAdminId = Setting::get('super_admin_id', null);

        // Prevent deleting the super-admin account
        if ($user->id == $superAdminId) {
            abort(403, 'Impossible de supprimer le super‑admin.');
        }

        // Allow only super-admin or admins (second-degree) to delete non-admin users
        if ($current->id == $superAdminId) {
            // super-admin can delete anyone (except himself handled above)
            $user->delete();
            return back()->with('success', 'Utilisateur supprimé.');
        }

        if (($current->role ?? null) === 'admin') {
            // admin cannot delete other admins
            if (($user->role ?? null) === 'admin') {
                abort(403, 'Un administrateur ne peut pas supprimer un autre administrateur.');
            }
            $user->delete();
            return back()->with('success', 'Utilisateur supprimé.');
        }

        abort(403, 'Non autorisé.');
    }

    public function restoreArchivedDonation($id)
    {
        $user = auth()->user();
        if (!$user || ($user->role ?? null) !== 'admin') {
            abort(403);
        }

        $archive = DonationArchive::findOrFail($id);

        // Restore into donations table
        $donation = Donation::create([
            'user_id' => $archive->user_id,
            'amount' => $archive->amount,
            'provider' => $archive->provider ?? 'external',
            'status' => $archive->status ?? 'paid',
            'external_reference' => $archive->external_reference,
        ]);

        // set created_at to donated_at if available
        if ($archive->donated_at) {
            $donation->created_at = $archive->donated_at;
            $donation->save();
        }

        // remove archive record
        $archive->delete();

        return back()->with('success', 'Don restauré depuis l\'archive.');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Category::create([
            'name' => $validated['name'],
            'slug' => str($validated['name'])->slug()->value(),
            'description' => $validated['description'] ?? null,
            'ordering' => Category::max('ordering') + 1,
        ]);

        return back()->with('success', 'Catégorie créée.');
    }
}
