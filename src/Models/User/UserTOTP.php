<?php
/**
 * Created for lv-app
 * Date: 03.04.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\User;


use OTPHP\Factory;
use OTPHP\OTPInterface;

class UserTOTP
{

    /**
     * @var OTPInterface
     * @link https://github.com/Spomky-Labs/otphp
     */
    private OTPInterface $totp;

    /** @var string[] */
    private array $recovery = [];

    public function __construct(string $provisioningUri)
    {
        $this->totp = Factory::loadFromProvisioningUri($provisioningUri);
        for ($i = 1; $i <= 10; $i++) {
            $this->recovery[] = $this->randomRecoveryCode();
        }
    }

    public function getUri(): string
    {
        return $this->totp->getProvisioningUri();
    }

    public function verify(string $code): bool
    {
        return $this->totp->verify($code) || $this->popRecoveryCode($code);
    }

    public function getRecovery(): array
    {
        return $this->recovery;
    }

    /**
     * Ключи восстановления при утере генератора одноразовых кодов
     * @param string $otp
     * @return bool
     */
    protected function popRecoveryCode(string $otp): bool
    {
        foreach ($this->recovery as $i => $code) {
            if ($code === $otp) {
                unset($this->recovery[$i]);
                return true;
            }
        }
        return false;
    }

    protected function randomRecoveryCode(): string
    {
        $key = md5(random_bytes(32));
        $key = str_split($key, 1);
        $key = array_map(function (string $char) {
            if (rand(1, 2) == 2) {
                return strtoupper($char);
            }
            return  strtolower($char);
        }, $key);
        shuffle($key);
        return implode('', $key);
    }

}