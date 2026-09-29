Task assigned: {{ $task->subject }}

{{ $actor->name }} assigned this task to you.

Due: {{ optional($task->due_on)?->toDateString() ?? 'Not set' }}
Priority: {{ $task->priority }}
Status: {{ $task->status }}

Open the CRM to review the task.
