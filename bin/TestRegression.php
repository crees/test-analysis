<?php
namespace TestAnalysis;

class TestRegression extends DatabaseCollection
{
    const TEST_ID = 'Test_id';
    const REGRESSION_KEY = 'regression_key';
    const REGRESSION_GRADIENT = 'regression_gradient';
    const REGRESSION_INTERCEPT = 'regression_intercept';
    const REGRESSION_ERROR = 'regression_error';
    
    public function __construct(array $details)
    {
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        }
        foreach ([self::TEST_ID, self::REGRESSION_KEY, 
            self::REGRESSION_GRADIENT, self::REGRESSION_INTERCEPT,
            self::REGRESSION_ERROR,
        ] as $key) {
            $this->details[$key] = $details[$key] ?? 0;
        }
    }
    
    function __destruct()
    {}
}

