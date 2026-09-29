<?php

namespace App\Http\Controllers;

use App\Actions\Import\ImportCsv;
use App\Http\Requests\StoreImportRequest;
use App\Support\ImportObjects;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function create(Request $request): Response
    {
        $user = $request->user();
        $objects = ImportObjects::objectsForUser($user);

        abort_if($objects === [], 403);

        $fields = [];
        foreach ($objects as $object) {
            $fields[$object['key']] = ImportObjects::fields($object['key']);
        }

        return Inertia::render('Import/Create', [
            'objects' => $objects,
            'fields' => $fields,
            'result' => $request->session()->get('import_result'),
        ]);
    }

    public function store(StoreImportRequest $request, ImportCsv $import): RedirectResponse
    {
        $data = $request->validated();
        $result = $import->handle(
            $request->user(),
            $data['object_type'],
            $request->file('file'),
            $data['mapping'],
            (bool) ($data['update_existing'] ?? false),
        );

        $errorToken = null;
        if ($result['errors'] !== []) {
            $errorToken = (string) Str::uuid();
            Cache::put($this->errorCacheKey($request->user()->id, $errorToken), $result['errors'], now()->addHour());
        }

        return redirect()
            ->route('import.create')
            ->with('import_result', [
                'imported' => $result['imported'],
                'updated' => $result['updated'],
                'failed' => $result['failed'],
                'error_token' => $errorToken,
            ])
            ->with('success', sprintf(
                'Import finished: %d inserted, %d updated, %d failed.',
                $result['imported'],
                $result['updated'],
                $result['failed'],
            ));
    }

    public function errors(Request $request, string $token): StreamedResponse
    {
        abort_unless($request->user() !== null, 403);

        $errors = Cache::get($this->errorCacheKey($request->user()->id, $token));
        abort_if(! is_array($errors), 404);

        $filename = 'import-errors-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($errors): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['row', 'errors', 'data']);
            foreach ($errors as $error) {
                fputcsv($out, [
                    $error['row'] ?? '',
                    $error['errors'] ?? '',
                    json_encode($error['data'] ?? [], JSON_UNESCAPED_UNICODE),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function errorCacheKey(int $userId, string $token): string
    {
        return "import-errors:{$userId}:{$token}";
    }
}
