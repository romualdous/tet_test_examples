<?php

namespace App\Exceptions;

use App\Models\User;

class ConversationException extends ApiException
{
    /**
     * @return static
     */
    public static function notEnoughCredits(): self
    {
        return new self("You do not have enough credits to start the call");
    }

    /**
     * @param User $listener
     * @return static
     */
    public static function notAvailableForCall(User $listener): self
    {
        return new self("User with ID: {$listener->id} is currently not available for call");
    }

    /**
     * @return static
     */
    public static function invalidParticipants(): self
    {
        return new self("Invalid conversation participants. Only customer can call listener, not vice versa");
    }

    /**
     * @return $this
     */
    public static function currentUserIsNotListener(): self
    {
        return new self("Currently signed in user is not a listener");
    }

    /**
     * @return static
     */
    public static function callCurrentlyHappening(): self
    {
        return new self("Call has already been initiated and is currently on-going");
    }

    /**
     * @return static
     */
    public static function callCannotBeAccepted(): self
    {
        return new self("Call cannot be accepted");
    }

    /**
     * @return static
     */
    public static function timestampsAreNotSet(): self
    {
        return new self("Call timestamps are not set properly (started_at and finished_at)");
    }

    /**
     * @return static
     */
    public static function timestampsHaveBeenManipulated(): self
    {
        return new self(
            "Call timestamps may have been manipulated on client side or server time has been malfunctioning. Charges made for this call might be incorrect"
        );
    }

    /**
     * @param array $possibleStatuses
     * @return $this
     */
    public static function invalidStatusProvided(array $possibleStatuses): self
    {
        $statuses = implode(', ', $possibleStatuses);

        return new self("Status provided is invalid. Possible statuses are {$statuses}");
    }

    /**
     * @return static
     */
    public static function callNotActive(): self
    {
        return new self("Call you are trying to finish, is not active");
    }

    /**
     * @return static
     */
    public static function activeCallAlreadyHappening(): self
    {
        return new self("There is already existing call happening between given customer and listener");
    }

    /**
     * @return static
     */
    public static function callCannotBeCancelled(): self
    {
        return new self("Call cannot be cancelled. For it to be cancellable, it should be initiated first");
    }

    /**
     * @return $this
     */
    public static function cannotCallYourself(): self
    {
        return new self("Call cannot be initiated for calling yourself");
    }

    /**
     * @return static
     */
    public static function userNotParticipant(): self
    {
        return new self("User is not a participant of the given call");
    }

    /**
     * @return $this
     */
    public static function callNotFinished(): self
    {
        return new self("Call hasn't been finished");
    }

    /**
     * @param User $user
     * @return static
     */
    public static function userHaveAlreadyBeenRated(User $user): self
    {
        return new self("User with ID of {$user->id} have already been rated for conversation");
    }

    /**
     *
     * @return static
     */
    public static function missingParametars(): self
    {
        return new self("At least one parametar must be set");
    }

    /**
     * @param User ...$participants
     * @return static
     */
    public static function deviceTokensNotSet(User ...$participants): self
    {
        $userWithNoToken = collect($participants)->first(fn (User $user) => !$user->device_token);

        return new self(
            "There is not device token set for user with ID: {$userWithNoToken->id}"
        );
    }
}
