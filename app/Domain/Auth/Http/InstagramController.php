<?php

namespace App\Domain\Auth\Http;

use App\Domain\Auth\Exceptions\InstagramException;
use App\Domain\Auth\InstagramMediaListQuery;
use App\Domain\Auth\InstagramService;
use App\Domain\Auth\OAuth2Provider;
use App\Http\Controllers\Controller;
use App\Repositories\ImagesRepository;
use App\Services\UsersService;
use App\UserProviderUid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstagramController extends Controller
{
    public function show(Request $request, InstagramService $instagramService): JsonResponse
    {
        $data = $this->validate($request, [
            'source' => 'sometimes',
        ]);

        return serialized(
            $instagramService->startAuthorization(Auth::user(), $data['source'] ?? null)
        );
    }

    public function showStatus(InstagramService $instagramService): JsonResponse
    {
        return serialized(
            $instagramService->isAuthorized(Auth::user())
        );
    }

    public function showMedia(Request $request, InstagramService $instagramService): JsonResponse
    {
        $params = $this->validate(
            $request,
            [
                'before' => 'sometimes',
                'after' => 'sometimes',
                'limit' => 'sometimes|integer|between:1,20',
            ]
        );

        $user = Auth::user();

        if (!$instagramService->isAuthorized($user)) {
            abort(403, 'Not authorized');
        } else {
            return serialized(
                $instagramService->getMediaList($user, InstagramMediaListQuery::createFromAttributes($params))
            );
        }
    }

    public function callback(Request $request, InstagramService $instagramService)
    {
        $user = Auth::user();

        $attributes = $this->validate(
            $request,
            [
                'code' => 'required_without:error',
                'state' => 'required_with:code',
                'error' => 'required_without:code',
                'error_reason' => 'required_with:error',
                'error_description' => 'required_with:error',
            ]
        );

        if (isset($attributes['code'])) {
            try {
                $instagramService->completeAuthorization($user, $attributes['state'], $attributes['code']);
            } catch (InstagramException $exception) {
                if ($exception->getMessage() === 'Invalid state') {
                    return redirect('/signin');
                }
            }

            switch ($instagramService->getAuthorizationSource($attributes['state'])) {
                case 'setup':
                    return redirect('/setup');
                case 'profile':
                    return redirect('/profile/profile');
                case 'photos':
                    return redirect('/profile/photos');
                default:
                    return redirect('/');
            }
        } else {
            logger('Instagram auth failure: ' . json_encode($attributes));

            return redirect('/profile/profile');
        }
    }

    public function connect(Request $request, InstagramService $instagramService): JsonResponse
    {
        $data = $this->validate($request, [
            'access_token' => 'required',
        ]);

        $user = Auth::user();

        $instagramService->authorizeFromShortLivedToken($user, $data['access_token']);

        return serialized($user);
    }

    public function disconnect(InstagramService $instagramService): JsonResponse
    {
        $user = Auth::user();

        $instagramService->removeAuthorization($user);

        return serialized($user);
    }

    public function leave(Request $request, InstagramService $instagramService)
    {
        $data = $this->validate(
            $request,
            [
                'signed_request' => 'required',
            ]
        );

        $data = $this->parseSignedRequest($data['signed_request']);
        $ownerId = $data['user_id'] ?? null;

        if (!$ownerId) {
            logger('Owner was not identified');
        } else {
            logger("$ownerId at instagram is leaving us");

            $user = $instagramService->getUserFromOwnerId($ownerId);

            if (!$user) {
                logger("No matching user for owner $ownerId");
            } else {
                $instagramService->removeAuthorization($user);
            }
        }

        logger('leave: ' . json_encode($request->all()));
    }

    public function clear(Request $request, InstagramService $instagramService, ImagesRepository $imagesRepository): JsonResponse
    {
        $data = $this->validate(
            $request,
            [
                'signed_request' => 'required',
            ]
        );

        $data = $this->parseSignedRequest($data['signed_request']);
        $ownerId = $data['user_id'] ?? null;

        if (!$ownerId) {
            logger('Owner was not identified');
        } else {
            logger("$ownerId at instagram is deleting data");

            $user = $instagramService->getUserFromOwnerId($ownerId);

            if (!$user) {
                logger("No matching user for owner $ownerId");
            } else {
                $instagramService->clearAuthorization($user);
                $imagesRepository->deleteInstagramImages($user);
            }
        }

        $code = base64UrlEncode($ownerId);

        return serialized(
            [
                'url' => trim(env('APP_URL'), '/') . "/auth/instagram/deletion/$code",
                'confirmation_code' => $code,
            ]
        );
    }

    public function showDeletionStatus(string $code, InstagramService $instagramService): JsonResponse
    {
        $ownerId = (int)base64UrlDecode($code);

        if (!$ownerId) {
            abort(422, 'Invalid code');
        }

        $user = $instagramService->getUserFromOwnerId($ownerId);

        return serialized($user ? 'User found' : 'User not found');
    }

    /**
     * @param $signed_request
     * @return mixed|null
     */
    private function parseSignedRequest($signed_request)
    {
        list($encoded_sig, $payload) = explode('.', $signed_request, 2);

        $secret = config('services.instagram.client_secret');

        // decode the data
        $sig = base64UrlDecode($encoded_sig);
        $data = json_decode(base64UrlDecode($payload), true);

        // confirm the signature
        $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);

        if ($sig !== $expected_sig) {
            abort(500, 'Bad Signed JSON signature!');
            return null;
        }

        return $data;
    }
}
