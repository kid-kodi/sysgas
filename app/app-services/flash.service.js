﻿(function () {
    'use strict';

    angular
        .module('app')
        .factory('FlashService', FlashService);

    FlashService.$inject = ['$rootScope','$timeout'];
    function FlashService($rootScope, $timeout) {
        var service = {};

        service.Success = Success;
        service.Error = Error;

        initService();

        return service;

        function clearFlashMessage() {
            var flash = $rootScope.flash;
            if (flash) {
                if (!flash.keepAfterLocationChange) {
                    delete $rootScope.flash;
                } else {
                    // only keep for a single location change
                    flash.keepAfterLocationChange = false;
                }
            }
        }

        function initService() {
            $rootScope.$on('$locationChangeStart', function () {
                clearFlashMessage();
            });
        }

        function Success(message, keepAfterLocationChange) {
            $rootScope.flash = {
                message: message,
                type: 'success', 
                keepAfterLocationChange: keepAfterLocationChange
            };

            $timeout(function () {
                clearFlashMessage();
            }, 5000);
        }

        function Error(message, keepAfterLocationChange) {
            $rootScope.flash = {
                message: message,
                type: 'error',
                keepAfterLocationChange: keepAfterLocationChange
            };
            $timeout(function () {
                clearFlashMessage();
            }, 5000);
        }
    }

})();