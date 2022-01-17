# Mini Shop 專案簡介
[[git]](https://github.com/xd820410/mini-shop)&ensp;&ensp;[[demo]](https://www.huhu543.click/)
---
## 簡述
>#### 採用Repository Pattern，
>#### 將功能細分拆到各Service、Repository的method，
>#### Repository調用Orm寫SQL，
>#### Service調用Repository&處理商務邏輯，
>#### Controller組合各Service整理成所需response，
>#### Middleware處理權限與request。
>#### 部份程式使用[Method injection](https://laravel.com/docs/8.x/container#method-invocation-and-injection)，以避免注入過多用不到的class。
## 訪客/會員購物車
>#### 訪客購物車存於session，會員購物車存資料庫，
>#### 用middleware MergeSessionCart()過濾登入後頁面
>#### (↑php artisan ui vue --auth預設登入後轉跳到/home)，
>#### 若session cart非空合併至user cart。
## 商品管理
>#### REST api佐以Laravel Sanctum， 
>#### 須帶符合權限的Bearer token才可得到response(除取全商品之外)。
>#### 管理頁面以middleware Manager()檢查須有管理員權限才可進入，
>#### 前後端分離，以jQuery ajax串Rest api撰寫。
>![](https://i.imgur.com/cbcCYpU.png)
## 優惠
```php
//CartController

public function getUserCart(CartService $cartService, DiscountService $discountService)
{
    try {
        $cart = App::call([new CartService, 'getUserCart'], ['userId' => Auth::user()->id]);
        if (!empty($cart)) {
            $cart = App::call([new CartService, 'fillItemDataInCart'], ['cart' => $cart]);

            $now = Carbon::now();
            $nowString = $now->toDateTimeString();
            $effectiveDiscount = $discountService->getByDate($nowString);

            if (!empty($effectiveDiscount)) {
                $cart = $cartService->calculateDiscount($cart, $effectiveDiscount);
            }
        }
        $total = $cartService->calculateTotal($cart);

        $returnMessage = [
            'result' => 'SUCCESS',
            'content' => $cart,
            'total' => $total,
        ];

        return response()->json($returnMessage, Response::HTTP_OK);
    } catch (Exception $e) {
        $errorMessage = [
            'result' => 'ERROR',
            'message' => $e->getMessage(),
        ];

        return response()->json($errorMessage, Response::HTTP_NOT_FOUND);
    }
}
```
>#### 先以getUserCart()取出購物車(僅有商品id與數量)，
>#### fillItemDataInCart()將商品資料填進去，
>#### (↑取商品資料不放在迴圈內，先整理購物車所有商品id，再用where in取商品資料，最後組合，避免N+1)
>#### 以Carbon當前時間找出正在進行的優惠，
>#### 最後用calculateDiscount組合購物車與優惠，計算出各商品優惠金額，
>#### 以組合小功能來完成功能。
```php
//CartService

public function calculateDiscount(Array $cart, $effectiveDiscount)
{
    foreach ($effectiveDiscount as $eachDiscount) {
        switch ($eachDiscount['type']) {
            //某商品滿件折
            case Discount::type_single_goods_quantity_threshold:
                //some calculation
                break;
        }
    }
    
    return $cart;
}
```
>#### 目前只有一種「[每A件B折](https://github.com/xd820410/mini-shop/blob/master/app/Services/CartService.php#L40)」優惠，
>#### 若須擴充，可再獨立一class，各method各類型優惠計算法，之後注入此method。
## 測試
```php
//DiscountTest

public function setUp(): void
{
    parent::setUp();

    config([
        'database.connections.mysql.database' => env('DB_TEST_DATABASE', 'test_dbname')
    ]);

    //seed裡面是 9527每兩個8折
    Artisan::call('db:seed --class=DiscountTestSeeder');
}
```
```php
/**
 * 檢查優惠區間是否生效(ForUserCart)
 * @dataProvider cartForTimeIntervalOfDiscount
 * @return void
 */
public function test_timeIntervalForUserCart($rawCart, $goodsData, $now, $expectedTotal)
{
    $this->partialMock(CartRepository::class, function (MockInterface $mock) use ($rawCart) {
        $userCart = [];
        $userCart['payload'] = $rawCart;
        $mock->shouldReceive('getByUserId')
            ->once()
            ->andReturn($userCart);
    });

    $this->partialMock(GoodsRepository::class, function (MockInterface $mock) use ($goodsData) {
        $mock->shouldReceive('getByIds')
            ->once()
            ->andReturn(collect($goodsData));
    });

    Carbon::setTestNow($now);
    //↓It works.
    //dump('now: ', Carbon::now());
    $this->login();
    $response = $this->get('/cart');
    $response->assertJson([
        'total' => $expectedTotal
    ], true);
}
```
```php
/**
 * @return array[]
 */
public function cartForTimeIntervalOfDiscount()
{
    return [
        [
            [
                'goods_9527' => [
                    'goods_id' => "9527",
                    'quantity' => 3,
                ]
            ],
            [
                [
                    'id' => 9527,
                    'title' => 'Goods for test',
                    'description' => null,
                    'price' => 100,
                    'image_path' => null
                ]
            ],
            Carbon::now(),
            260
        ],
        [
            [
                'goods_9527' => [
                    'goods_id' => "9527",
                    'quantity' => 3,
                ]
            ],
            [
                [
                    'id' => 9527,
                    'title' => 'Goods for test',
                    'description' => null,
                    'price' => 100,
                    'image_path' => null
                ]
            ],
            new Carbon('2030-01-23 11:53:20'),
            300
        ]
    ];
}
```
>#### 使用測試資料庫，並插入測試優惠(9527商品每兩件8折)，
>#### 以Mockery模擬購物車資料與商品資料(皆為id9527商品)，
>#### 最後以Carbon設定測試時間，一組於時間內，一組於時間外，
>#### 測試回傳金額是否如預期。
>#### 另一[test_quantityThreshold()](https://github.com/xd820410/mini-shop/blob/master/tests/Feature/DiscountTest.php#L93)則是雷同的方式，但改為測試商品數量
>#### 「剛好超過一組數量門檻」、「符合三組(一組以上)數量門檻」、「超過三組(一組以上)數量門檻」，
>#### 來測試金額計算是否如預期。
