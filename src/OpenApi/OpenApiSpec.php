<?php
/**
 * NFC Inventory — Physical-to-Digital State Tracker
 * Global OpenAPI 3.0 Specification Configuration
 */
declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'API and URL Routing documentation for the open-source, self-hosted NFC Inventory Tracker. Featuring automatic hardware NTAG chip serial number normalization and dynamic linking.',
    title: 'NFC Inventory API',
    contact: new OA\Contact(name: 'Azzie Development', url: 'https://azziedevelopment.com')
)]
#[OA\Server(
    url: '/',
    description: 'Self-Hosted Application Root Runtime'
)]
class OpenApiSpec
{
    // Global schema and server definitions container
}
