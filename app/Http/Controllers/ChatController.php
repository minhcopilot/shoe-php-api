<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Events\NewMessage;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'content' => $request->content,
            'is_read' => false
        ]);

        $messageWithRelations = Message::with(['sender', 'receiver'])
            ->find($message->id);

        broadcast(new NewMessage($messageWithRelations))->toOthers();

        return response()->json($messageWithRelations);
    }

    public function getMessages(Request $request)
    {
        $messages = Message::where(function($query) use ($request) {
            $query->where('sender_id', auth()->id())
                  ->where('receiver_id', $request->user_id);
        })->orWhere(function($query) use ($request) {
            $query->where('sender_id', $request->user_id)
                  ->where('receiver_id', auth()->id());
        })->with(['sender', 'receiver'])->get();

        Message::where('sender_id', $request->user_id)
            ->where('receiver_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    public function getChats()
    {
        try {
            $userIds = Message::where('receiver_id', auth()->id())
                ->orWhere('sender_id', auth()->id())
                ->whereHas('sender')
                ->select('sender_id')
                ->distinct()
                ->get()
                ->pluck('sender_id')
                ->filter(function($id) {
                    return $id != auth()->id();
                });

            $chats = collect();
            
            foreach ($userIds as $userId) {
                $user = \App\Models\User::find($userId);
                if (!$user) continue;

                $lastMessage = Message::where(function($query) use ($userId) {
                    $query->where('sender_id', $userId)
                          ->where('receiver_id', auth()->id());
                })->orWhere(function($query) use ($userId) {
                    $query->where('sender_id', auth()->id())
                          ->where('receiver_id', $userId);
                })
                ->latest()
                ->first();

                if (!$lastMessage) continue;

                $unreadCount = Message::where('sender_id', $userId)
                    ->where('receiver_id', auth()->id())
                    ->where('is_read', false)
                    ->count();

                $chats->push([
                    'user_id' => $userId,
                    'user_name' => $user->name,
                    'last_message' => $lastMessage->content,
                    'last_message_time' => $lastMessage->created_at,
                    'unread_count' => $unreadCount
                ]);
            }

            return response()->json($chats->sortByDesc('last_message_time')->values());
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching chats',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}