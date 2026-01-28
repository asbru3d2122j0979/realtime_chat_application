<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        // Generate or get session ID from cookie
        $sessionId = $request->cookie('session_id') ?? Str::random(20);
        
        // Generate device ID
        $deviceId = $request->cookie('device_id') ?? Str::random(10);
        
        // Get messages for this session
        $messages = ChatMessage::where('session_id', $sessionId)->get();
        
        return response()
            ->view('chat', compact('messages', 'sessionId', 'deviceId'))
            ->cookie('session_id', $sessionId, 60*24*30)
            ->cookie('device_id', $deviceId, 60*24*30);
    }
    
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required',
            'session_id' => 'required',
            'device_id' => 'required'
        ]);
        
        // Save message
        $chatMessage = ChatMessage::create([
            'message' => $request->message,
            'device_id' => $request->device_id,
            'session_id' => $request->session_id
        ]);
        
        // Broadcast event
        broadcast(new MessageSent($request->message, $request->session_id));
        
        return response()->json(['status' => 'Message sent']);
    }
    
    public function getMessages($sessionId)
    {
        $messages = ChatMessage::where('session_id', $sessionId)->get();
        return response()->json($messages);
    }
}