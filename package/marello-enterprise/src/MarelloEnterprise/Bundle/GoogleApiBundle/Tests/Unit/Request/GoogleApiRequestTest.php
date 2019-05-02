<?php

namespace MarelloEnterprise\Bundle\GoogleApiBundle\Tests\Unit\Request;

use PHPUnit\Framework\TestCase;

use MarelloEnterprise\Bundle\GoogleApiBundle\Request\GoogleApiRequest;

class GoogleApiRequestTest extends TestCase
{
    public function testGetters()
    {
        $params = [
            GoogleApiRequest::FIELD_REQUEST_PARAMETERS => ['parameter1' => 'value1', 'parameter2' => 'value2']
        ];

        $request = new GoogleApiRequest($params);

        $getterValues = [
            GoogleApiRequest::FIELD_REQUEST_PARAMETERS => $request->getRequestParameters()
        ];

        $this->assertEquals($params, $getterValues);
    }
}
