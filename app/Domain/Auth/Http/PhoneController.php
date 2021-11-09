<?php

namespace App\Domain\Auth\Http;

use App\Domain\Auth\PhoneLoginService;
use App\Http\Controllers\Controller;
use App\Services\UsersService;
use App\Services\SecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class PhoneController extends Controller
{
    /**
     * @param Request $request
     * @param SecurityService $securityService
     * @param PhoneLoginService $phoneLoginService
     * @return Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(
        Request $request,
        SecurityService $securityService,
        PhoneLoginService $phoneLoginService
    ): Response {
        $data = $this->validate(
            $request,
            [
                'phone' => 'required',
                'captchaToken' => 'required',
            ]
        );

        if (!$securityService->verifyCaptcha($data['captchaToken'])) {
            abort(403, 'Captcha not verified');
        }

        $phoneLoginService->start($data['phone']);

        return response()->noContent();
    }

    /**
     * @param Request $request
     * @param SecurityService $securityService
     * @param PhoneLoginService $phoneLoginService
     * @return Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function storeApi(
        Request $request,
        PhoneLoginService $phoneLoginService
    ): Response {
        $data = $this->validate(
            $request,
            [
                'phone' => 'required',
            ]
        );

        $phoneLoginService->start($data['phone']);

        return response()->noContent();
    }

    /**
     * @param Request $request
     * @param PhoneLoginService $phoneLoginService
     * @param UsersService $usersRepository
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function complete(Request $request, PhoneLoginService $phoneLoginService, UsersService $usersRepository): Response
    {
        $data = $this->validate($request, [
            'phone' => 'required',
            'code' => 'required|numeric',
        ]);

        if (!$phoneLoginService->verify($data['phone'], $data['code'])) {
            abort(401);
        } else {
            if ($user = $usersRepository->fromPhoneNumber($data['phone'])) {
                Auth::login($user);
            }

            return response()->noContent();
        }
    }

    public function showResend(Request $request, PhoneLoginService $phoneLoginService): JsonResponse
    {
        $data = $this->validate($request, [
            'phone' => 'required',
        ]);

        return serialized($phoneLoginService->getResendTimeout($data['phone']));
    }

    public function resend(Request $request, PhoneLoginService $phoneLoginService): JsonResponse
    {
        $data = $this->validate($request, [
            'phone' => 'required',
        ]);

        if (!$phoneLoginService->resend($data['phone'])) {
            abort(422);
        } else {
            return serialized($phoneLoginService->getResendTimeout($data['phone']));
        }
    }
}
