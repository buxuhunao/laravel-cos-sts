<?php

namespace Buxuhunao\CosSts;

use Exception;
use GuzzleHttp\Client;

class StsClient
{
    protected const DOMAIN = 'sts.tencentcloudapi.com';
    protected const URL = 'https://sts.tencentcloudapi.com/';

    protected string $app_id;
    protected string $secret_id;
    protected string $secret_key;
    protected string $region;
    protected string $bucket;

    protected array|string $action;
    protected array|string $resourcePrefix;

    protected string $effect = 'allow';
    protected int $durationSeconds = 1800;

    protected array $policies = [];

    public function __construct(array $config)
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    // 临时密钥计算样例
    protected function _hex2bin($data): bool|string
    {
        $len = strlen($data);

        return pack("H" . $len, $data);
    }

    // obj 转 query string
    protected function json2str(array $obj, $notEncode = false): string
    {
        $array = [];
        ksort($obj);

        foreach ($obj as $key => $val) {
            $array[] = $key . '=' . ($notEncode ? $val : rawurlencode($val));
        }

        return join('&', $array);
    }

    // 计算临时密钥用的签名
    protected function getSignature($opt, $method): string
    {
        $formatString = $method . self::DOMAIN . '/?' . $this->json2str($opt, true);
        $sign = hash_hmac('sha1', $formatString, $this->secret_key);

        return base64_encode($this->_hex2bin($sign));
    }

    // v2接口的key首字母小写，v3改成大写，此处做了向下兼容
    protected function backwardCompat(array $result): array
    {
        $compat = [];

        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $compat[lcfirst($key)] = $this->backwardCompat($value);
            } elseif ($key == 'Token') {
                $compat['sessionToken'] = $value;
            } else {
                $compat[lcfirst($key)] = $value;
            }
        }

        return $compat;
    }

    public function getTempKeys(array $config = null)
    {
        if (! is_null($config)) {
            if (empty($config['allowActions']) || empty($config['allowPrefixes'])) {
                throw new Exception('allowActions and allowPrefixes is required');
            }

            if (! empty($config['durationSeconds']) && is_int($config['durationSeconds'])) {
                $this->setDurationSeconds($config['durationSeconds']);
            }

            $this->setPolicy($config['allowActions'], $config['allowPrefixes']);
        }

        return $this->requestTempKeys();
    }

    protected function requestTempKeys()
    {
        $params = $this->handleRequestParam();

        try {
            $response = (new Client())->post(self::URL, ['form_params' => $params]);
            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['Response'])) {
                $result = $result['Response'];

                if (isset($result['Error'])) {
                    throw new Exception("get cam failed");
                }

                $result['startTime'] = $result['ExpiredTime'] - $this->durationSeconds;
            }

            return $this->backwardCompat($result);
        } catch (Exception $e) {
            $result = isset($result) ? "error: " . $e->getMessage() : json_encode($result);

            throw new Exception($result);
        }
    }

    protected function handleRequestParam()
    {
        $policy = $this->generatePolicy();
        $policyStr = str_replace('\\/', '/', json_encode($policy));

        $params = [
            'SecretId' => $this->secret_id,
            'Timestamp' => time(),
            'Nonce' => rand(10000, 20000),
            'Action' => 'GetFederationToken',
            'DurationSeconds' => $this->durationSeconds,
            'Version' => '2018-08-13',
            'Name' => 'cos',
            'Region' => $this->region,
            'Policy' => urlencode($policyStr)
        ];
        $params['Signature'] = $this->getSignature($params, 'POST');

        return $params;
    }

    protected function generatePolicy(): array
    {
        if (empty($this->policies)) {
            throw new Exception('policy need be set first');
        }

        $statement = [];

        foreach ($this->policies as $policy) {
            $item['action'] = $policy['actions'];
            $item['effect'] = $this->effect;
            $item['resource'] = $this->generateResource($policy['prefixes']);
            $statement[] = $item;
        }

        return ['version' => '2.0', 'statement' => $statement];
    }

    protected function generateResource($prefixes): array
    {
        $data = [];

        foreach ($prefixes as $prefix) {
            if (! str_starts_with($prefix, '/')) {
                $prefix = '/' . $prefix;
            }

            $data[] = sprintf(
                'qcs::cos:%s:uid/%s:%s-%s%s',
                $this->region,
                $this->app_id,
                $this->bucket,
                $this->app_id,
                $prefix
            );
        }

        return $data;
    }

    public function setEffect($isAllow): static
    {
        $this->effect = $isAllow ? 'allow' : 'deny';

        return $this;
    }

    public function setDurationSeconds($seconds): static
    {
        $this->durationSeconds = $seconds;

        return $this;
    }

    public function setPolicy($allowActions, $allowPrefixes): static
    {
        $item['actions'] = is_array($allowActions) ? $allowActions : [$allowActions];
        $item['prefixes'] = is_array($allowPrefixes) ? $allowPrefixes : [$allowPrefixes];

        $this->policies[] = $item;

        return $this;
    }
}
