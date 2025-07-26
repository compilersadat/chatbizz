<?php
namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Firestore;

class FirebaseUserService
{
    public static function firestore(): Firestore
    {
        $firebaseCredentialsPath = env('FIREBASE_CREDENTIALS');
        $factory = (new Factory)->withServiceAccount($firebaseCredentialsPath);
        return $factory->createFirestore();
    }
}
