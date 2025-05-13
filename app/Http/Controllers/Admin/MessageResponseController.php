<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\MessageResponse;
use App\Models\Message;
use Auth;
use Illuminate\Http\Request;
use Mail;

class MessageResponseController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Message $message, Request $request)
    {
        $message->update([
            'status' => 'replied'
        ]);

        $message->response()->create([
            'message' => $request->post('message'),
            'created_by' => Auth::user()->id
        ]);

        Mail::to($message->email)->send(new MessageResponse($message->subject, $request->post('message')));

        return redirect()->back()->with('success', 'Response was sent!');
    }
}
