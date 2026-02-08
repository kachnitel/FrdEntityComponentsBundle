<!--- BEGIN HEADER -->
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
<!--- END HEADER -->

## [0.0.1](https://github.com/kachnitel/FrdEntityComponentsBundle/compare/0.0.0...0.0.1) (2026-02-08)

### Features

* Add AttachmentManager Live Component ([c5acb6](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/c5acb6d2f1168dffdb6d180f72b9a9c880fceec6))
* Add category colors to TagManager badges ([833516](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/833516ca931a51d044e986bed15c2ed12cd326be))
* Add CommentsManager component ([d269e7](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/d269e7c3ba1f6a495e59bab649917f32c9574a2f))
* SelectRelationship component ([e97190](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/e971905ea611d66e7ea6780c60c299bcdfdd6945))
* Support Symfony 8 ([5c3b18](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/5c3b18a4a07ccdcb8cb843fa772255d30f226a55))

### Bug Fixes

* Add missing NotFoundHttpException import ([b884c5](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/b884c5bb5b1ce605a7277892f3ee4361551d46aa))
* Bundle initialization; add tests ([bc8dd7](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/bc8dd79d9092e84315540c99e11c02b245f04a3e))
* Correct params for comment delete action ([11ebf8](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/11ebf8228b951b5ebddb0bcd354b681205a62b97))
* Explicitly register ColorConverterExtension as Twig extension ([ebe793](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/ebe793be1a79f5253fa85bb6e692365bba0c0298))
* Fix AttachmentManager submit action and add TagManager support ([5b4205](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/5b4205a4a93f4e34ce2132df825e4d0dbcb8db07))
* Fix file upload by using proper form submission ([893d40](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/893d40502249c00f72ec3eb9e0f4cd70bda4566a))
* K:Entity namespaces in components ([9c4758](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/9c4758710a56abb09581072136a586dc99bb3f1e))
* Loading placeholders ([12b16e](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/12b16eabd124da97a170aa32d13c4fdcdb1f23b9))
* Register ColorConverterExtension in bundle build() method ([d98732](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/d98732ad8da10394bdb61a5193319ce4e02423a6))
* Remove redundant attachmentClass definition ([ad64ec](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/ad64ec4f4a17cc7bf5d4d273e2b256c8fa140b11))
* Remove redundant tagClass definition ([31906b](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/31906b92f024a2a9f956b96da832ca3adc1d9281))
* Store entityId/entityClass instead of entity in AttachmentManager ([ebd7f7](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/ebd7f716053e0020b550d3ae8224467ef2aa6478))
* Store tag IDs instead of objects in LiveProp ([1d1f12](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/1d1f126c79a275022ffcff0169bc4758843fb35b))
* Use correct LiveComponent files modifier syntax ([0fe194](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/0fe1941cb788f084c281f59b7bdb50617bc1d465))
* Use legacy getTypes() API from PropertyInfo ([d9cf67](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/d9cf674a1a0563d0d416287cc4a4921d85739ab0))
* Use released kachnitel/color-converter ([a25f43](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/a25f43c2c10ffd8d555890458e300d7627aa0b52))
* Wrong file upload param ([5bd16c](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/5bd16ca5db3717896860461cc911c8d93c840114))

##### Test

* Update TagManagerTest to use IDs ([d3cf1c](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/d3cf1c01e2a8ffa704429500ab2528435324b31b))

##### Tests

* Avoid `Test code or tested code did not remove its own exception handlers` warning ([faac12](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/faac122bb78660a9a7d63fa54e02018af71f4671))

### Code Refactoring

* Extract color conversion logic to frd/color-converter library ([03a7d8](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/03a7d88ce7a6ea029d255bc1154cee4e885f1ec8))
* Move into Kachnitel namespace ([acb082](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/acb082d4d62873e549d78a8b0f18171273c5f4d3))
* Simplify AttachmentManager layout to match original design ([45015c](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/45015ce55fe827e1bfea2f60d38da0138d89d87d))

### Tests

* Add ColorConverterExtension tests ([95a712](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/95a7128baba4c813419e2758dc6d79ce9e8c9f66))
* Add CommentsManager tests and improve test infrastructure ([865e12](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/865e12b8629a6fbc5eeaf6d40176de4bd82f0596))

### Documentation

* Correct namespace ([fd1dc4](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/fd1dc412cee2260755fd2359999b7c8e9a1f9102))


---

## [0.0.0](https://github.com/kachnitel/FrdEntityComponentsBundle/compare/0.0.0...0.0.0) (2026-02-08)


---

## [0.0.0](https://github.com/kachnitel/FrdEntityComponentsBundle/compare/0ae260f82d18bfc0de869e8ed4f77444d92a582b...0.0.0) (2026-02-08)

### Features

* Add AttachmentManager Live Component ([c5acb6](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/c5acb6d2f1168dffdb6d180f72b9a9c880fceec6))
* Add category colors to TagManager badges ([833516](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/833516ca931a51d044e986bed15c2ed12cd326be))
* Add CommentsManager component ([d269e7](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/d269e7c3ba1f6a495e59bab649917f32c9574a2f))
* SelectRelationship component ([e97190](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/e971905ea611d66e7ea6780c60c299bcdfdd6945))
* Support Symfony 8 ([5c3b18](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/5c3b18a4a07ccdcb8cb843fa772255d30f226a55))

### Bug Fixes

* Add missing NotFoundHttpException import ([b884c5](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/b884c5bb5b1ce605a7277892f3ee4361551d46aa))
* Bundle initialization; add tests ([bc8dd7](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/bc8dd79d9092e84315540c99e11c02b245f04a3e))
* Correct params for comment delete action ([11ebf8](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/11ebf8228b951b5ebddb0bcd354b681205a62b97))
* Explicitly register ColorConverterExtension as Twig extension ([ebe793](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/ebe793be1a79f5253fa85bb6e692365bba0c0298))
* Fix AttachmentManager submit action and add TagManager support ([5b4205](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/5b4205a4a93f4e34ce2132df825e4d0dbcb8db07))
* Fix file upload by using proper form submission ([893d40](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/893d40502249c00f72ec3eb9e0f4cd70bda4566a))
* K:Entity namespaces in components ([9c4758](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/9c4758710a56abb09581072136a586dc99bb3f1e))
* Loading placeholders ([12b16e](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/12b16eabd124da97a170aa32d13c4fdcdb1f23b9))
* Register ColorConverterExtension in bundle build() method ([d98732](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/d98732ad8da10394bdb61a5193319ce4e02423a6))
* Remove redundant attachmentClass definition ([ad64ec](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/ad64ec4f4a17cc7bf5d4d273e2b256c8fa140b11))
* Remove redundant tagClass definition ([31906b](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/31906b92f024a2a9f956b96da832ca3adc1d9281))
* Store entityId/entityClass instead of entity in AttachmentManager ([ebd7f7](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/ebd7f716053e0020b550d3ae8224467ef2aa6478))
* Store tag IDs instead of objects in LiveProp ([1d1f12](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/1d1f126c79a275022ffcff0169bc4758843fb35b))
* Use correct LiveComponent files modifier syntax ([0fe194](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/0fe1941cb788f084c281f59b7bdb50617bc1d465))
* Use legacy getTypes() API from PropertyInfo ([d9cf67](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/d9cf674a1a0563d0d416287cc4a4921d85739ab0))
* Use released kachnitel/color-converter ([a25f43](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/a25f43c2c10ffd8d555890458e300d7627aa0b52))
* Wrong file upload param ([5bd16c](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/5bd16ca5db3717896860461cc911c8d93c840114))

##### Test

* Update TagManagerTest to use IDs ([d3cf1c](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/d3cf1c01e2a8ffa704429500ab2528435324b31b))

##### Tests

* Avoid `Test code or tested code did not remove its own exception handlers` warning ([faac12](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/faac122bb78660a9a7d63fa54e02018af71f4671))

### Code Refactoring

* Extract color conversion logic to frd/color-converter library ([03a7d8](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/03a7d88ce7a6ea029d255bc1154cee4e885f1ec8))
* Move into Kachnitel namespace ([acb082](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/acb082d4d62873e549d78a8b0f18171273c5f4d3))
* Simplify AttachmentManager layout to match original design ([45015c](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/45015ce55fe827e1bfea2f60d38da0138d89d87d))

### Tests

* Add ColorConverterExtension tests ([95a712](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/95a7128baba4c813419e2758dc6d79ce9e8c9f66))
* Add CommentsManager tests and improve test infrastructure ([865e12](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/865e12b8629a6fbc5eeaf6d40176de4bd82f0596))

### Documentation

* Correct namespace ([fd1dc4](https://github.com/kachnitel/FrdEntityComponentsBundle/commit/fd1dc412cee2260755fd2359999b7c8e9a1f9102))


---

