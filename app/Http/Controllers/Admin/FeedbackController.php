<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function index(Request $request): Response
    {
        $showResolved = $request->boolean('resolved');

        $items = Feedback::query()
            ->with(['user:id,name', 'company:id,name,type'])
            ->when(! $showResolved, fn ($q) => $q->whereNull('resolved_at'))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (Feedback $item) => [
                'id' => $item->id,
                'message' => $item->message,
                'screen' => $item->screen,
                'user' => $item->user?->name,
                'company' => $item->company?->name,
                'companyType' => $item->company?->type->label(),
                'resolved' => $item->isResolved(),
                'createdAt' => $item->created_at->toIso8601String(),
            ]);

        return Inertia::render('admin/feedback/index', [
            'items' => $items,
            'showResolved' => $showResolved,
            'pending' => Feedback::whereNull('resolved_at')->count(),
        ]);
    }

    public function toggle(Feedback $feedback): RedirectResponse
    {
        $feedback->update([
            'resolved_at' => $feedback->isResolved() ? null : now(),
        ]);

        return back();
    }
}
