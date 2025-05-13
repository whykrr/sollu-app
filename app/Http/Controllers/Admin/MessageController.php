<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    public function __construct()
    {
        Gate::authorize('message', 'cms');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'from', 'to', 'status']);

        return inertia('Message/Index', [
            'messages' => Message::newest()->filters($filters)->paginate(20),
            'filters' => $filters
        ]);
    }

    public function show(Message $message)
    {
        if ($message->status === 'unread') {
            $message->update([
                'status' => 'read',
                'read_at' => Carbon::now(),
                'read_by' => Auth::user()->id
            ]);
        }

        return inertia('Message/Show', [
            'message' => $message->load(['response', 'response.responder']),
        ]);
    }
}
