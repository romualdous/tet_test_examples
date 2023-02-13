<?php

namespace App\Exceptions;

class UserException extends ApiException
{
    /**
     * @param array $allowedTypes
     * @return static
     */
    public static function givenTypeInvalid(array $allowedTypes): self
    {
        $types = implode(' or ', $allowedTypes);

        return new static(
            "Invalid user type given. User can only be {$types}"
        );
    }

    /**
     * @param array $allowedStatuses
     * @return static
     */
    public static function givenStatusInvalid(array $allowedStatuses): self
    {
        $statuses = implode(' or ', $allowedStatuses);

        return new static(
            "Invalid user status given. User can only be {$statuses}"
        );
    }

    /**
     * @param array $allowedGenders
     * @return $this
     */
    public static function givenGenderInvalid(array $allowedGenders): self
    {
        $types = implode(' or ', $allowedGenders);

        return new static(
            "Invalid user gender given. User can only be {$types}"
        );
    }

    /**
     * @return static
     */
    public static function invalidEmailFormat(): self
    {
        return new static(
            "Invalid e-mail address provided"
        );
    }

    /**
     * @return static
     */
    public static function userIsNotValidListener(): self
    {
        return new self(
            "User is not a valid listener"
        );
    }
}
