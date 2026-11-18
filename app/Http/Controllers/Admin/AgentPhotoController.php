<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PhotoRequest;
use App\Models\Agent;
use App\Services\PhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgentPhotoController extends Controller
{
    public function __construct(private PhotoService $photos) {}

    public function store(PhotoRequest $request, Agent $agent): JsonResponse|RedirectResponse
    {
        $this->photos->remplacer($agent, $request->file('photo'));

        return $this->reponse($request, $agent, 'La photo a été mise à jour.');
    }

    public function destroy(Request $request, Agent $agent): JsonResponse|RedirectResponse
    {
        $this->authorize('updatePhoto', $agent);
        $this->photos->supprimer($agent);

        return $this->reponse($request, $agent, 'La photo a été supprimée. L\'avatar par défaut est affiché.');
    }

    private function reponse(Request $request, Agent $agent, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'photo' => $agent->photo_url,
                'miniature' => $agent->miniature_url,
                'aPhoto' => (bool) $agent->photo,
            ]);
        }

        return back()->with('succes', $message);
    }
}
