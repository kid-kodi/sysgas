(function () { 
    'use strict';

    angular.module('app')
        .factory('printer', ['$rootScope', '$compile', '$http', '$timeout', '$q',
            function ($rootScope, $compile, $http, $timeout, $q) {
            var printHtml = function (html) {
                var deferred = $q.defer();
                var hiddenFrame = angular.element('<iframe id="myiframe" style="display: none"></iframe>');
                var body = angular.element(document).find('body').eq(0);
                body.append(hiddenFrame);

                var myiframe = document.getElementById('myiframe');

                myiframe.contentWindow.printAndRemove = function () {
                    myiframe.contentWindow.print();
                    myiframe.remove();
                };
                var htmlContent = "<!doctype html>" +
                            "<html>" +
                                '<body onload="printAndRemove();">' +
                                    html +
                                '</body>' +
                            "</html>";
                var doc = myiframe.contentWindow.document.open("text/html", "replace");
                doc.write(htmlContent);
                deferred.resolve();
                doc.close();
                return deferred.promise;
            };

            var openNewWindow = function (html) {
                var newWindow = window.open("printTest.html");
                newWindow.addEventListener('load', function () {
                    $(newWindow.document.body).html(html);
                }, false);
            };

            var print = function (templateUrl, data) {
                console.log('## to be print...');
                console.log(data);
                $http.get(templateUrl).success(function (template) {
                    var printScope = $rootScope.$new()
                    angular.extend(printScope, data);
                    console.log(printScope);
                    var element = $compile(angular.element('<div>' + template + '</div>'))(printScope);
                    var waitForRenderAndPrint = function () {
                        if (printScope.$$phase || $http.pendingRequests.length) {
                            $timeout(waitForRenderAndPrint);
                        } else {
                            // Replace printHtml with openNewWindow for debugging
                            printHtml(element.html());
                            printScope.$destroy();
                        }
                    };
                    waitForRenderAndPrint();
                });
            };

            var printFromScope = function (templateUrl, scope) {
                $rootScope.isBeingPrinted = true;
                $http.get(templateUrl).success(function (template) {
                    var printScope = scope;
                    var element = $compile(angular.element('<div>' + template + '</div>'))(printScope);
                    var waitForRenderAndPrint = function () {
                        if (printScope.$$phase || $http.pendingRequests.length) {
                            $timeout(waitForRenderAndPrint);
                        } else {
                            printHtml(element.html()).then(function () {
                                $rootScope.isBeingPrinted = false;
                            });

                        }
                    };
                    waitForRenderAndPrint();
                });
            };
            return {
                print: print,
                printFromScope: printFromScope
            }
        }]);
})();