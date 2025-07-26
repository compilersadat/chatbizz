<?php
// app/Http/Controllers/Api/ChatController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Google\Cloud\Firestore\FieldValue;


class ChatController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function send(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'sender_id' => 'required|integer',
            'receiver_id' => 'required|integer',
            'message' => 'required|string',
            'sender_type' => 'required|in:user,delivery',
        ]);

        $chat = Chat::create($request->all());

        // Prepare data for Firestore
        $data = [
            'order_id' => $request->order_id,
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'sender_type' => $request->sender_type,
            'created_at' => FieldValue::serverTimestamp()
        ];

        // Relay message to Firestore of the recipient
            $this->firebase->sendMessageToDelivery($request->order_id, $data);
            $this->firebase->sendMessageToUser($request->order_id, $data);
          
           
        }

        return response()->json(['success' => true, 'chat' => $chat]);
    }
