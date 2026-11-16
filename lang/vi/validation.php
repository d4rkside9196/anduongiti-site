<?php

// Vietnamese validation messages for the public contact form (and any
// future front-end forms). English messages are provided by the framework's
// built-in en/validation.php — this file only needs to keep parity of
// meaning for the vi locale.
return [
    'accepted' => 'Trường :attribute phải được chấp nhận.',
    'boolean' => 'Trường :attribute phải là true hoặc false.',
    'email' => 'Trường :attribute phải là một địa chỉ email hợp lệ.',
    'in' => 'Giá trị đã chọn cho :attribute không hợp lệ.',
    'integer' => 'Trường :attribute phải là một số nguyên.',
    'max' => [
        'array' => 'Trường :attribute không được có quá :max phần tử.',
        'file' => 'Trường :attribute không được lớn hơn :max kilobyte.',
        'numeric' => 'Trường :attribute không được lớn hơn :max.',
        'string' => 'Trường :attribute không được lớn hơn :max ký tự.',
    ],
    'min' => [
        'array' => 'Trường :attribute phải có ít nhất :min phần tử.',
        'file' => 'Trường :attribute phải tối thiểu :min kilobyte.',
        'numeric' => 'Trường :attribute phải tối thiểu :min.',
        'string' => 'Trường :attribute phải có ít nhất :min ký tự.',
    ],
    'required' => 'Trường :attribute là bắt buộc.',
    'required_if' => 'Trường :attribute là bắt buộc khi :other là :value.',
    'required_with' => 'Trường :attribute là bắt buộc khi :values có mặt.',
    'string' => 'Trường :attribute phải là một chuỗi ký tự.',
    'url' => 'Trường :attribute phải là một URL hợp lệ.',
    'attributes' => [
        'name' => 'họ và tên',
        'company' => 'công ty',
        'email' => 'email',
        'country' => 'quốc gia',
        'product' => 'sản phẩm quan tâm',
        'message' => 'nội dung',
        'phone' => 'điện thoại',
        'zalo' => 'Zalo',
        'whatsapp' => 'WhatsApp',
        'facebook' => 'Facebook',
        'shopee' => 'Shopee',
        'alibaba' => 'Alibaba',
        'linkedin' => 'LinkedIn',
    ],
];
