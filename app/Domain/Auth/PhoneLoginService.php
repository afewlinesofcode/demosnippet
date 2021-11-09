<?php


namespace App\Domain\Auth;


use App\Contracts\Adapters\RedisAdapterInterface;
use App\Contracts\Adapters\TwilioAdapterInterface;

class PhoneLoginService
{
    /**
     * @var TwilioAdapterInterface
     */
    private $twilioAdapter;

    /**
     * @var RedisAdapterInterface
     */
    private $redisAdapter;

    public function __construct(TwilioAdapterInterface $twilioAdapter, RedisAdapterInterface $redisAdapter)
    {
        $this->twilioAdapter = $twilioAdapter;
        $this->redisAdapter = $redisAdapter;
    }

    public function normalizePhoneNumber(string $number): string
    {
        return preg_replace('/[^+\d]/', '', $number);
    }

    public function start(string $phoneNumber): string
    {
        $phoneNumber = $this->normalizePhoneNumber($phoneNumber);
        $code = $this->createVerificationCode($phoneNumber);
        $this->sendVerificationCode($phoneNumber, $code);
        return $code;
    }

    public function verify(string $phoneNumber, string $code): bool
    {
        $phoneNumber = $this->normalizePhoneNumber($phoneNumber);
        $storedCode = $this->popVerificationCode($phoneNumber);
        return $code && $storedCode && $code === $storedCode;
    }

    public function resend(string $phoneNumber): bool
    {
        $phoneNumber = $this->normalizePhoneNumber($phoneNumber);

        if (!$this->canResend($phoneNumber)) {
            return false;
        }

        $code = $this->getStoredVerificationCode($phoneNumber);

        if (!$code) {
            return false;
        }

        $this->sendVerificationCode($phoneNumber, $code);

        return true;
    }

    public function getResendTimeout(string $phoneNumber): int
    {
        $phoneNumber = $this->normalizePhoneNumber($phoneNumber);
        $resendAt = $this->getResendAt($phoneNumber);
        return max($resendAt - time(), 0);
    }

    private function createVerificationCode(string $phoneNumber): string
    {
        $code = (string)rand(1000, 9999);

        $key = $this->redisKey($phoneNumber);

        $this->redisAdapter->del($key);

        for ($i = 0, $count = config('auth.phone.attempts'); $i < $count; ++$i) {
            $this->redisAdapter->lpush($key, $code);
        }

        $this->redisAdapter->expire($key, config('auth.phone.expire'));

        return $code;
    }

    private function getStoredVerificationCode(string $phoneNumber): ?string
    {
        $key = $this->redisKey($phoneNumber);
        return $this->redisAdapter->lrange($key, 0, 0)[0] ?? null;
    }

    private function popVerificationCode(string $phoneNumber): ?string
    {
        $key = $this->redisKey($phoneNumber);
        return $this->redisAdapter->lpop($key);
    }

    private function sendVerificationCode(string $phoneNumber, $code): void
    {
        $this->twilioAdapter->sendSms($phoneNumber, $code);
        $this->scheduleResend($phoneNumber);
    }

    private function scheduleResend(string $phoneNumber): void
    {
        $key = $this->redisResendKey($phoneNumber);
        $this->redisAdapter->set($key, time() + config('auth.phone.resendInterval'));
    }

    private function canResend(string $phoneNumber): bool
    {
        $resendAt = $this->getResendAt($phoneNumber);
        return $resendAt && $resendAt < time();
    }

    private function getResendAt(string $phoneNumber): int
    {
        $key = $this->redisResendKey($phoneNumber);
        return $this->redisAdapter->get($key) ?? 0;
    }

    private function redisKey($phoneNumber): string
    {
        return 'auth:phone:' . base64_encode($phoneNumber);
    }

    private function redisResendKey($phoneNumber): string
    {
        return 'auth:phone:resend:' . base64_encode($phoneNumber);
    }
}
