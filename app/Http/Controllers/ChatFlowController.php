<?php

namespace App\Http\Controllers;

use App\Models\BotQuestion;
use App\Models\BotOption;
use Illuminate\Http\Request;

class ChatFlowController extends Controller
{
    // List all questions with option count
    public function index()
    {
        $questions = BotQuestion::withCount('options')
            ->orderBy('id')
            ->get();

        return view('bot_flows.list', compact('questions'));
    }

    // Show create form
    public function create()
    {
        return view('bot_flows.create');
    }

    // Handle save of question + options
    public function store(Request $request)
    {
        // 1) Validate basic question data
        $validated = $request->validate([
            'key'         => 'required|string|max:100|unique:bot_questions,key',
            'service'     => 'nullable|string|max:100',
            'message'     => 'required|string',
            'store_field' => 'nullable|string|max:255',

            // options are arrays
            'options.*.match_value' => 'required|string|max:100',
            'options.*.next_key'    => 'nullable|string|max:100',
            'options.*.set_service' => 'nullable|string|max:100',
            'options.*.store_field' => 'nullable|string|max:255',
            'options.*.store_value' => 'nullable|string|max:255',
            'options.*.is_default'  => 'nullable|boolean',
        ]);

        // 2) Create the question
        $question = BotQuestion::create([
            'key'         => $validated['key'],
            'service'     => $validated['service'] ?? null,
            'message'     => $validated['message'],
            'store_field' => $validated['store_field'] ?? null,
        ]);

        // 3) Create options (if any submitted)
        $options = $request->input('options', []);

        foreach ($options as $opt) {
            // Skip completely empty rows
            if (empty($opt['match_value'])) {
                continue;
            }

            BotOption::create([
                'bot_question_id' => $question->id,
                'match_value'     => $opt['match_value'],
                'next_key'        => $opt['next_key'] ?? null,
                'set_service'     => $opt['set_service'] ?? null,
                'store_field'     => $opt['store_field'] ?? null,
                'store_value'     => $opt['store_value'] ?? null,
                'is_default'      => !empty($opt['is_default']) ? 1 : 0,
            ]);
        }

        return redirect()
            ->route('bot.flow.index')
            ->with('success', 'Bot question and options created successfully.');
    }
}
