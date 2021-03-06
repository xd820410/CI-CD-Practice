<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class GoodsTest extends TestCase
{
    protected $token;

    public function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.mysql.database' => env('DB_TEST_DATABASE', 'test_dbname')
        ]);
    }

    //檢查http status
    public function test_getGoodsListHttpStatus()
    {
        $response = $this->get('/api/goods');
        //dd($response);
        $response->assertStatus(200);
    }

    //檢查json結構
    public function test_getGoodsListResponseFormat()
    {
        $response = $this->get('/api/goods');
        $response->assertJsonStructure([
            'result',
            'message' => [
                '*' => [
                    'title'
                ]
            ]
        ]);
    }

    /**
     * 檢查json結構(data provider應用)
     * @dataProvider getGoodsTestData
     * @return void
     */
    public function test_creatGoodsResponseFormat($testResource, $exceptedDescription)
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['edit']
        );

        $response = $this->postJson('/api/goods', $testResource);
        $response->assertJsonStructure([
            'result',
            'message' => [
                'title'
            ]
        ]);
    }

    /**
     * 回應是否為201
     * @dataProvider getGoodsTestData
     * @return void
     */
    public function test_creatGoodsSuccessfully($testResource, $exceptedDescription)
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['edit']
        );

        $response = $this->postJson('/api/goods', $testResource);
        //$response->dumpHeaders();
        $response->assertCreated();
    }

    /**
     * 從回應中取值、檢查json內的值
     * @dataProvider getGoodsTestData
     * @return void
     */
    public function test_creatGoodsDefaultValue($testResource, $exceptedDescription)
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['edit']
        );

        $result = $this->postJson('/api/goods', $testResource);
        $goodsId = $result->decodeResponseJson()['message']['id'];

        $response = $this->get('/api/goods/' . $goodsId);
        //$response->dump();
        $response->assertJson([
            'message' => [
                'description' => $exceptedDescription
            ]
        ], true);
    }

    /**
     * 回應是否為204
     * @dataProvider getGoodsTestData
     * @return void
     */
    public function test_updateGoodsSuccessfully($testResource, $exceptedDescription)
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['edit']
        );

        $result = $this->postJson('/api/goods', $testResource);
        $goodsId = $result->decodeResponseJson()['message']['id'];

        //$response = $this->patchJson('/api/goods/99999', $testResource);
        $response = $this->patchJson('/api/goods/' . $goodsId, $testResource);
        $response->assertNoContent();
    }

    /**
     * 回應是否為404
     * @dataProvider getGoodsTestData
     * @return void
     */
    public function test_failToUpdateGoods($testResource, $exceptedDescription)
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['edit']
        );

        $response = $this->patchJson('/api/goods/99999', $testResource);
        $response->assertNotFound();
    }

    /**
     * @return array[]
     */
    public function getGoodsTestData()
    {
        return [
            [
                ['title' => '高級漢堡王早餐券', 'price' => 200],
                null
            ],
            [
                ['title' => '高級肥皂一組', 'price' => 200, 'description' => '不要撿'],
                '不要撿'
            ]
        ];
    }
}
