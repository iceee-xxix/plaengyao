<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\ClientManager;

class EmailController extends Controller
{
    public function index()
    {
        $cm = new ClientManager();
        $client = $cm->make([
            'host'          => env('IMAP_HOST'),
            'port'          => env('IMAP_PORT'),
            'encryption'    => env('IMAP_ENCRYPTION'),
            'validate_cert' => env('IMAP_VALIDATE_CERT'),
            'username'      => env('IMAP_USERNAME'),
            'password'      => env('IMAP_PASSWORD'),
            'protocol'      => 'imap'
        ]);

        $client->connect();

        $folder = $client->getFolder('INBOX');
        $messages = $folder->query()->all()->get();

        foreach ($messages as $message) {
            dd($message);
            echo $message->getSubject() . '<br>';
            echo $message->getTextBody() . '<br>';
            if ($message->hasAttachments()) {
                $attachments = $message->getAttachments();

                foreach ($attachments as $attachment) {
                    $attachment->save(storage_path('app/attachments/'));
                    echo 'Attachment saved: ' . $attachment->name . '<br>';
                }
            } else {
                echo 'No attachments.<br>';
            }
        }
    }
}
