<?php
namespace Tests\Feature;

use App\Helpers\StorageHelper;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class StorageHelperResponseTest extends TestCase
{
    /**
     * Regression: serving a file from the local private disk must not throw a
     * TypeError. response()->file() returns a BinaryFileResponse, so the
     * method's return type must accept it (Symfony's base Response).
     */
    public function test_response_serves_local_file_without_type_error(): void
    {
        $path = 'id_images/test/'.uniqid().'.txt';
        Storage::disk('private')->put($path, 'sample-data');

        $resp = StorageHelper::response($path);

        $this->assertInstanceOf(Response::class, $resp);
        $this->assertSame(200, $resp->getStatusCode());

        Storage::disk('private')->delete($path);
    }
}
