<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiChatLog extends Model
{
    protected $table = 'ai_chat_logs';
    protected $fillable = ['user_id', 'pertanyaan', 'jawaban', 'tool_calls'];
}
