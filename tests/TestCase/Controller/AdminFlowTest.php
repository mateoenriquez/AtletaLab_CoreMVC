<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Application;
use App\Model\Table\ObjectivesTable;
use App\Test\Support\TestDatabase;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AdminFlowTest extends TestCase
{
    use IntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        TestDatabase::reset();
        $this->configApplication(Application::class, [CONFIG]);
        $this->session(['Auth' => ['id' => 1]]);
    }

    public static function resources(): iterable
    {
        foreach (['roles' => 'Roles', 'archetypes' => 'Archetypes', 'users' => 'Users', 'objectives' => 'Objectives', 'exercises' => 'Exercises', 'routines' => 'Routines', 'routine-exercises' => 'RoutineExercises', 'workout-logs' => 'WorkoutLogs'] as $path => $model) {
            yield $model => [$path, $model];
        }
    }

    public static function pages(): iterable
    {
        foreach (self::resources() as [$path, $model]) {
            foreach (['', '/view/1', '/add', '/edit/1'] as $action) {
                yield $path . $action => ['/' . $path . $action];
            }
        }
    }

    #[DataProvider('pages')]
    public function testRendersEveryCrudTemplate(string $path): void
    {
        $this->get($path);
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">' . $body);
        foreach ($document->getElementsByTagName('form') as $form) {
            $this->assertTrue($form->hasAttribute('novalidate'));
        }
        $xpath = new \DOMXPath($document);
        $this->assertCount(0, $xpath->query('//input[@name="roleId" or @name="archetypeId" or @name="athleteId" or @name="trainerId" or @name="objectiveId" or @name="routineId" or @name="exerciseId" or @name="routineExerciseId"]'));
        $this->assertHeaderContains('X-Content-Type-Options', 'nosniff');
    }

    public function testDashboardReadsRealAggregates(): void
    {
        $this->get('/');
        $this->assertResponseOk();
        $this->assertResponseContains('El progreso empieza aquí.');
        $this->assertResponseContains('Base de fuerza');
        $this->assertResponseContains('Press de banca');
    }

    #[DataProvider('resources')]
    public function testCreatesEachResourceViaHttp(string $path, string $model): void
    {
        $this->enableCsrfToken();
        $before = TableRegistry::getTableLocator()->get($model)->find()->count();
        $this->post('/' . $path . '/add', TestDatabase::valid($model));
        $this->assertResponseCode(302);
        $this->assertHeaderContains('Location', '/' . $path . '/view/');
        $this->assertSame($before + 1, TableRegistry::getTableLocator()->get($model)->find()->count());
    }

    #[DataProvider('resources')]
    public function testEditsEachResourceViaHttp(string $path, string $model): void
    {
        $this->enableCsrfToken();
        $data = TestDatabase::valid($model);
        if ($model === 'Roles') { $data['code'] = 'ADMIN'; }
        if ($model === 'Users') { $data['roleId'] = 1; }
        $this->post('/' . $path . '/edit/1', $data);
        $this->assertResponseCode(302);
        $this->assertHeaderContains('Location', '/' . $path . '/view/1');
    }

    public function testRejectsIncoherentRepsAndRendersSpanishErrorTwice(): void
    {
        $this->enableCsrfToken();
        $data = array_replace(TestDatabase::valid('Objectives'), ['targetRepMin' => '12', 'targetRepMax' => '8']);
        $this->post('/objectives/add', $data);
        $this->assertResponseCode(422);
        $this->assertResponseContains(ObjectivesTable::REP_ERROR);
        $this->assertGreaterThanOrEqual(2, substr_count((string)$this->_response->getBody(), ObjectivesTable::REP_ERROR));
        $this->assertFalse(TableRegistry::getTableLocator()->get('Objectives')->exists(['code' => 'TEST_OBJ']));
    }

    public static function malformedInputs(): iterable
    {
        yield ['archetypes', 'Archetypes', 'maxFatigueThreshold'];
        yield ['objectives', 'Objectives', 'targetRepMin'];
        yield ['exercises', 'Exercises', 'muscleGroup'];
        yield ['users', 'Users', 'birthDate'];
        yield ['users', 'Users', 'password'];
        yield ['routine-exercises', 'RoutineExercises', 'exerciseId'];
        yield ['routines', 'Routines', 'athleteId'];
        yield ['workout-logs', 'WorkoutLogs', 'rpe'];
    }

    #[DataProvider('malformedInputs')]
    public function testMalformedHttpFieldsReturnValidationErrors(string $path, string $model, string $field): void
    {
        $this->enableCsrfToken();
        $data = array_replace(TestDatabase::valid($model), [$field => ['invalid']]);
        $this->post('/' . $path . '/add', $data);
        $this->assertResponseCode(422);
        $this->assertResponseContains('No se guardaron los datos.');
    }

    public function testExerciseEndpointReturnsOnlyChosenMuscleWithCoefficient(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/exercises/by-muscle-group?muscleGroup=Pecho');
        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $body = json_decode((string)$this->_response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $body['data']);
        $this->assertEqualsCanonicalizing([1, 2], array_column($body['data'], 'id'));
        $this->assertStringContainsString('coef.', $body['data'][0]['label']);
        $this->assertArrayHasKey('fatigueCoefficient', $body['data'][0]);
    }

    public function testAthleteEndpointFiltersByRoleAndArchetypeWithoutPasswords(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/users/athletes-by-archetype/1');
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEqualsCanonicalizing([4, 7], array_column($body['data'], 'id'));
        $this->assertResponseNotContains('password');
        $this->assertResponseNotContains('$2y$');
        $this->assertStringContainsString('·', $body['data'][0]['label']);
    }

    public function testAssignmentEndpointIncludesExerciseSetsAndReps(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/routine-exercises/by-routine/1');
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEqualsCanonicalizing([1, 2, 3], array_column($body['data'], 'id'));
        $this->assertArrayHasKey('targetSets', $body['data'][0]);
        $this->assertArrayHasKey('targetReps', $body['data'][0]);
        $this->assertStringContainsString('×', $body['data'][0]['label']);
    }

    public function testInvalidEndpointInputsReturnJsonErrors(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/exercises/by-muscle-group?muscleGroup[]=Pecho');
        $this->assertResponseCode(400);
        $this->assertContentType('application/json');
        $this->get('/users/athletes-by-archetype/999');
        $this->assertResponseCode(404);
        $this->get('/routine-exercises/by-routine/999');
        $this->assertResponseCode(404);
    }

    public function testRejectsTamperedCascadeAndMissingChildOnEdit(): void
    {
        $this->enableCsrfToken();
        $data = array_replace(TestDatabase::valid('RoutineExercises'), ['exerciseId' => 3, 'muscleGroupFilter' => 'Pecho']);
        $this->post('/routine-exercises/add', $data);
        $this->assertResponseCode(422);
        $data = TestDatabase::valid('WorkoutLogs');
        unset($data['routineExerciseId']);
        $this->post('/workout-logs/edit/1', $data);
        $this->assertResponseCode(422);
        $data = array_replace(TestDatabase::valid('Routines'), ['athleteId' => 5, 'archetypeFilter' => 1]);
        $this->post('/routines/add', $data);
        $this->assertResponseCode(422);
    }

    public function testRetainsCascadeSelectionsWhenEditing(): void
    {
        foreach (['/routine-exercises/edit/1' => ['Pecho', 'Press de banca'], '/routines/edit/1' => ['Lucía Mendoza', 'Adaptación inicial'], '/workout-logs/edit/1' => ['Base de fuerza', 'Press de banca']] as $url => $labels) {
            $this->get($url);
            $this->assertResponseOk();
            foreach ($labels as $label) {
                $this->assertResponseContains($label);
            }
            $this->assertResponseContains('selected="selected"');
        }
    }

    public function testRequiresAuthenticationForHtmlAndJson(): void
    {
        $this->session(['Auth' => null]);
        $this->get('/routines');
        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/exercises/by-muscle-group?muscleGroup=Pecho');
        $this->assertResponseCode(401);
        $this->assertContentType('application/json');
    }

    public function testRejectsNonAdminAndRechecksRoleFromDatabase(): void
    {
        $this->session(['Auth' => ['id' => 2, 'roleId' => 1]]);
        $this->get('/users');
        $this->assertResponseCode(403);
    }

    public function testLoginWorksWithHashAndRejectsExternalRedirect(): void
    {
        $users = TableRegistry::getTableLocator()->get('Users');
        $user = $users->patchEntity($users->get(1), ['password' => 'Nueva-clave-2026']);
        $users->saveOrFail($user);
        $this->session(['Auth' => null]);
        $this->enableCsrfToken();
        $this->post('/users/login?redirect=https%3A%2F%2Fevil.example', ['email' => 'admin@atletalab.test', 'password' => 'Nueva-clave-2026']);
        $this->assertRedirect(['controller' => 'Dashboard', 'action' => 'index']);
        $this->assertSession(1, 'Auth.id');
    }

    public function testCsrfIsRequiredForMutationAndGetCannotDelete(): void
    {
        $this->post('/roles/add', TestDatabase::valid('Roles'));
        $this->assertResponseCode(403);
        $this->get('/roles/delete/3');
        $this->assertResponseCode(404);
        $this->assertTrue(TableRegistry::getTableLocator()->get('Roles')->exists(['id' => 3]));
    }

    public function testMalformedLoginDoesNotReachPasswordHasher(): void
    {
        $this->session(['Auth' => null]);
        $this->enableCsrfToken();
        $this->post('/users/login', ['email' => 'admin@atletalab.test', 'password' => ['invalid']]);
        $this->assertResponseCode(400);
    }

    public function testSelfDeletionAndSelfDemotionAreBlocked(): void
    {
        $this->enableCsrfToken();
        $this->post('/users/delete/1');
        $this->assertResponseCode(302);
        $this->assertTrue(TableRegistry::getTableLocator()->get('Users')->exists(['id' => 1]));
        $data = array_replace(TestDatabase::valid('Users'), ['roleId' => 2]);
        $this->post('/users/edit/1', $data);
        $this->assertResponseCode(422);
        $this->assertSame(1, TableRegistry::getTableLocator()->get('Users')->get(1)->roleId);
    }

    public function testLoginThrottlingAndSecurityHeaders(): void
    {
        $this->session(['Auth' => null]);
        $this->enableCsrfToken();
        $this->configRequest(['environment' => ['REMOTE_ADDR' => '127.0.0.1']]);
        Cache::write(hash('sha256', '127.0.0.1'), ['attempts' => 10, 'resetAt' => time() + 900], 'login');
        $this->post('/users/login', ['email' => 'invalido@atletalab.test', 'password' => 'incorrecta']);
        $this->assertResponseCode(429);
        $this->assertGreaterThan(0, (int)$this->_response->getHeaderLine('Retry-After'));
        $this->assertLessThanOrEqual(900, (int)$this->_response->getHeaderLine('Retry-After'));
        $this->assertHeaderContains('Content-Security-Policy', "frame-ancestors 'none'");
    }
}
