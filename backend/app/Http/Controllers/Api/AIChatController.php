<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiChatLog;
use App\Services\AIService;
use Illuminate\Http\Request;

class AIChatController extends Controller
{
    public function __construct(protected AIService $ai) {}

    public function chat(Request $request)
    {
        $request->validate(['pertanyaan' => 'required|string|max:1000']);

        $hasil = $this->ai->chat($request->input('pertanyaan'), $request->user());

        AiChatLog::create([
            'user_id' => $request->user()->id,
            'pertanyaan' => $request->input('pertanyaan'),
            'jawaban' => $hasil['jawaban'],
            'tool_calls' => json_encode(['tool' => $hasil['tool_dipakai']]),
        ]);

        return response()->json($hasil);
    }

    public function riwayat(Request $request)
    {
        return AiChatLog::where('user_id', $request->user()->id)->latest()->limit(50)->get();
    }
}
