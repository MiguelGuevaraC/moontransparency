<?php

namespace Tests\Feature;

use Tests\TestCase;

class OpenApiDocumentationTest extends TestCase
{
    public function test_openapi_contract_is_generated_and_covers_modified_apis(): void
    {
        $path = storage_path('api-docs/api-docs.json');
        $this->assertFileExists($path);

        $document = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('3.0.0', $document['openapi']);

        $requiredOperations = [
            '/moontransparency/public/api/response-survey' => ['post'],
            '/moontransparency/public/api/response-survey/{id}' => ['post'],
            '/moontransparency/public/api/response-survey/{id}/finalize' => ['post'],
            '/moontransparency/public/api/offline-sync' => ['post'],
            '/moontransparency/public/api/survey/{id}/clean-participations' => ['post'],
            '/moontransparency/public/api/surveyed' => ['get'],
            '/moontransparency/public/api/surveyed/{id}/calculator' => ['get'],
            '/moontransparency/public/api/surveyed/{id}' => ['get', 'delete'],
            '/moontransparency/public/api/calculator/co2/configuration' => ['get'],
            '/moontransparency/public/api/calculator/co2/history' => ['get'],
            '/moontransparency/public/api/calculator/co2' => ['post'],
            '/moontransparency/public/api/surveyed/{id}/reopen' => ['post'],
            '/moontransparency/public/api/user' => ['get', 'post'],
            '/moontransparency/public/api/user/{id}' => ['get', 'put', 'delete'],
            '/moontransparency/public/api/rol' => ['get', 'post'],
            '/moontransparency/public/api/rol/{id}' => ['get', 'put', 'delete'],
            '/moontransparency/public/api/rol/{id}/menus' => ['put'],
            '/moontransparency/public/api/permission' => ['get'],
            '/moontransparency/public/api/menu' => ['get'],
        ];

        foreach ($requiredOperations as $operationPath => $methods) {
            foreach ($methods as $method) {
                $this->assertArrayHasKey($operationPath, $document['paths']);
                $this->assertArrayHasKey($method, $document['paths'][$operationPath]);
            }
        }

        foreach (['Surveyed', 'SurveyedUpsertRequest', 'SurveyedMeasurement', 'GeobosquesMap', 'OfflineSyncRequest', 'OfflineSyncResponse', 'SurveyCleanupInput', 'SurveyCleanupResult', 'User', 'Rol', 'Permission', 'Menu'] as $schema) {
            $this->assertArrayHasKey($schema, $document['components']['schemas']);
        }

        $this->assertArrayNotHasKey(
            'security',
            $document['paths']['/moontransparency/public/api/response-survey']['post']
        );
        $this->assertSame(
            [['bearerAuth' => []]],
            $document['paths']['/moontransparency/public/api/surveyed']['get']['security']
        );

        $documentedCodes = [];
        $operationIds = [];
        foreach ($document['paths'] as $pathItem) {
            foreach ($pathItem as $operation) {
                if (! is_array($operation) || ! isset($operation['responses'])) {
                    continue;
                }

                $documentedCodes = array_merge(
                    $documentedCodes,
                    array_map('strval', array_keys($operation['responses']))
                );
                if (isset($operation['operationId'])) {
                    $operationIds[] = $operation['operationId'];
                }
            }
        }

        foreach (['401', '403', '404', '409', '422'] as $statusCode) {
            $this->assertContains($statusCode, $documentedCodes);
        }
        $this->assertSame($operationIds, array_values(array_unique($operationIds)));

        preg_match_all(
            '~#/components/schemas/([^"/]+)~',
            json_encode($document, JSON_THROW_ON_ERROR),
            $matches
        );
        foreach (array_unique($matches[1]) as $referencedSchema) {
            $this->assertArrayHasKey($referencedSchema, $document['components']['schemas']);
        }
    }
}
