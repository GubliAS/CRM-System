<?php

use App\Mail\TaskAssignedMail;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

function stage11User(string $slug = 'sales-rep'): User
{
    $role = Role::query()->firstOrCreate(
        ['slug' => $slug],
        ['name' => $slug],
    );

    return User::factory()->create([
        'role_id' => $role->id,
    ]);
}

test('a bad CSV row is reported and not inserted', function () {
    $user = stage11User();

    $csv = UploadedFile::fake()->createWithContent(
        'leads.csv',
        "Last name,Company,Email\n".
        "Good,Acme,good@example.com\n".
        ",Missing Company,bad@example.com\n"
    );

    $response = $this->actingAs($user)->post(route('import.store'), [
        'object_type' => 'lead',
        'file' => $csv,
        'update_existing' => false,
        'mapping' => [
            'last_name' => 0,
            'company' => 1,
            'email' => 2,
        ],
    ]);

    $response->assertRedirect(route('import.create'));

    expect(Lead::query()->count())->toBe(1)
        ->and(Lead::query()->where('email', 'good@example.com')->exists())->toBeTrue()
        ->and(Lead::query()->where('email', 'bad@example.com')->exists())->toBeFalse();

    $result = session('import_result');
    expect($result['imported'])->toBe(1)
        ->and($result['failed'])->toBe(1)
        ->and($result['error_token'])->not->toBeNull();

    $download = $this->actingAs($user)
        ->get(route('import.errors', $result['error_token']));

    $download->assertOk();
    expect($download->headers->get('content-disposition'))->toContain('attachment')
        ->and($download->streamedContent())->toContain('last name');
});

test('assigning a task queues a mailable', function () {
    Mail::fake();

    $actor = stage11User();
    $assignee = stage11User();

    $task = Task::factory()->create([
        'owner_id' => $actor->id,
        'created_by' => $actor->id,
        'updated_by' => $actor->id,
        'assigned_to_id' => $actor->id,
        'subject' => 'Call the prospect',
    ]);

    $this->actingAs($actor)
        ->post(route('tasks.assign', $task), [
            'assigned_to_id' => $assignee->id,
        ])
        ->assertRedirect();

    expect($task->fresh()->assigned_to_id)->toBe($assignee->id);

    Mail::assertQueued(TaskAssignedMail::class, function (TaskAssignedMail $mail) use ($assignee, $task): bool {
        return $mail->assignee->is($assignee)
            && $mail->task->is($task);
    });

    expect(ActivityLog::query()
        ->where('subject_type', Task::class)
        ->where('subject_id', $task->id)
        ->where('action', 'email_sent')
        ->exists())->toBeTrue();
});
