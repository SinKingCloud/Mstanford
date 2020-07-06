<?php
use Systems\Route;
Route::Add("/","/Index/Index");
Route::Add("/GetUserInfo","/Index/Login");
Route::Add("/GetCourseList","/Index/Course");
Route::Add("/GetCourseInfo","/Index/Info");
Route::Add("/GetVideo","/Index/Video");
Route::Add("/video","/Index/Index/Video");
Route::Add("/test","/Index/Test");