<?php

declare(strict_types=1);

namespace App\Http\Controllers\Source;

use App\Domains\Source\Actions\DeleteSource;
use App\Domains\Source\Actions\RetryGeneration;
use App\Domains\Source\Actions\StoreUploadedSource;
use App\Domains\Source\Data\SourcePanelData;
use App\Domains\Source\Data\StoreSourceData;
use App\Domains\Source\Models\Source;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SourceController extends Controller
{
    /**
     * Show everything this person has uploaded and the topics it produced.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('source/Index', SourcePanelData::forUser($user));
    }

    /**
     * Accept an uploaded document and return without waiting on any analysis.
     */
    public function store(
        Request $request,
        StoreSourceData $data,
        StoreUploadedSource $storeUploadedSource,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $storeUploadedSource($user, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document uploaded.')]);

        return back();
    }

    /**
     * Queue analysis again for a document whose run failed.
     */
    public function retry(Source $source, RetryGeneration $retryGeneration): RedirectResponse
    {
        Gate::authorize('retry', $source);

        $retryGeneration($source);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Analysis queued again.')]);

        return back();
    }

    /**
     * Delete a document together with everything generated from it.
     */
    public function destroy(Source $source, DeleteSource $deleteSource): RedirectResponse
    {
        Gate::authorize('delete', $source);

        $deleteSource($source);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document deleted.')]);

        return back();
    }
}
