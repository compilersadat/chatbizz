<?php
// app/Services/FirebaseService.php
namespace App\Services;

use Kreait\Firebase\Factory;

class FirebaseService
{
    protected $userFirestore;
    protected $deliveryFirestore;

    public function __construct()
    {
        $firebaseCredentialsPath = env('FIREBASE_CREDENTIALS');
        $firebasePartnerCredentialsPath = env('FIREBASE_PARTNER_CREDENTIALS');
        $this->userFirestore = (new Factory)
            ->withServiceAccount($firebaseCredentialsPath)
            ->createFirestore()
            ->database();

        $this->deliveryFirestore = (new Factory)
            ->withServiceAccount($firebasePartnerCredentialsPath)
            ->createFirestore()
            ->database();
    }

    public function sendMessageToUser($orderId, $data)
    {
        // Add to Firestore under a collection, e.g., "chats/{orderId}/messages"
        $this->userFirestore
            ->collection('delivery_chats')
            ->document((string)$orderId)
            ->collection('messages')
            ->add($data);
    }

    public function sendMessageToDelivery($orderId, $data)
    {
        $this->deliveryFirestore
            ->collection('delivery_chats')
            ->document((string)$orderId)
            ->collection('messages')
            ->add($data);
    }
}
