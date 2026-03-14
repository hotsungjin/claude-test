var ImageModule = {
    validateImage: function (image) {
        if (image.files && image.files.length > 0 && (
            image.files[0].size > 1024 * 100 || !image.files[0].type.match(/image\/(jpg|jpeg|gif|png)/g))) {
            alert('첨부할 수 없는 파일입니다.');
            return false;
        }

        return true;
    },
    imageCustom: function (imageFile) {
        if (!this.validateImage(imageFile)) {
            $('#image-preview-layer').hide();

            return false;
        }

        if ($('input[name=image_type][value=default]:checked').length === 1) {
            $('input[name=image_type][value=custom]').trigger('click');
        }

        if (imageFile.files) {
            var file = imageFile.files[0];
            var size = file.size;

            if (file.size >= 1024) {
                size = Math.round(size / 1024) + 'KB';
            } else {
                size += 'B';
            }

            var reader = new FileReader();
            reader.onload = function (readerFile) {
                $('#image-preview').attr('src', readerFile.target.result);
                $('#image-preview-size').text(size);
                $('#image-preview-type').text(file.type.replace(/image\/(jpg|jpeg|gif|png)/g, '$1').toUpperCase());
                $('#image-preview-layer').show();
                $('.image-preview-details').show();
            }
            reader.readAsDataURL(imageFile.files[0]);
        }
    },
    details: function (image) {
        $('#image-preview-dimensions').text(image.width + 'px X ' + image.height + 'px');
    }
};
var AdminForm = {
    isCustomImageRegistered: false,
    config: {
        elem: null,
        effect: null,
        commonImages: null,
        effectImage: null
    },
    tmp: {
        count: 6,
        minSize: 10,
        maxSize: 30,
        image: 'snowflake_1.png',
        speed: 2,
        popcorn_speed: 30,
        opacity: 1
    },
    init: function (config) {
        this.config = config;
        this.config.elem = $('#snow-target');

        var type = $('input[name=effect_type]:checked').val();
        var count = $('input[name=effect_amount]:checked').val();
        var speed = $('input[name=effect_speed]:checked').val();
        var imageType = $('input[name=image_type]:checked').val();
        var opacity = $('input[name=effect_opacity]:checked').val();

        if (imageType === 'default') {
            this.tmp.image = this.config.commonImages + $('input[name=effect_image]:checked').val();
        } else {
            this.isCustomImageRegistered = true;
            this.imageType(imageType);
            $('#image-preview').attr('src', this.config.commonImages + this.config.effectImage);
            $('#image-preview-layer').show();
            $('.image-preview-details').hide();
        }

        this.tmp.count = this.getFlakeCount(count);
        this.tmp.speed = this.getSpeed(speed);
        this.tmp.opacity = Number(opacity) / 100;
        this.effect(type);

        var effect_name_len = $('input[name="effect_name"]').val().trim().length;
        $('#word_count').text(effect_name_len);
    },
    effect: function (type) {
        type = Number(type);

        switch (type) {
            case 1:
                if (this.config.effect instanceof PopCornWrapper) {
                    this.config.effect.clearPopcorn();
                }
                this.config.effect = new SnowFall(this.config.elem);
                this.config.effect.setMinSize(this.tmp.minSize);
                this.config.effect.setMaxSize(this.tmp.maxSize);
                this.config.effect.setImage(this.tmp.image);
                this.config.effect.setMaxSpeed(this.tmp.speed);
                this.config.effect.setMinSpeed(this.tmp.speed);
                this.config.effect.setFlakeCount(this.tmp.count);
                this.config.effect.setOpacity(this.tmp.opacity);
                var twinkle = $('input[name=effect_type_twinkle]').prop('checked');
                this.config.effect.setTwinkle(twinkle);
                this.config.effect.setStraight();
                $('input[name=effect_type_twinkle]').prop('disabled', false);
                break;
            case 2:
                if (this.config.effect instanceof PopCornWrapper) {
                    this.config.effect.clearPopcorn();
                }
                this.config.effect = new SnowFall(this.config.elem);
                this.config.effect.setMinSize(this.tmp.minSize);
                this.config.effect.setMaxSize(this.tmp.maxSize);
                this.config.effect.setImage(this.tmp.image);
                this.config.effect.setMaxSpeed(this.tmp.speed);
                this.config.effect.setMinSpeed(this.tmp.speed);
                this.config.effect.setFlakeCount(this.tmp.count);
                this.config.effect.setOpacity(this.tmp.opacity);
                var twinkle = $('input[name=effect_type_twinkle]').prop('checked');
                this.config.effect.setTwinkle(twinkle);
                this.config.effect.setMoving();
                $('input[name=effect_type_twinkle]').prop('disabled', false);
                break;
            case 3:
                if (this.config.effect instanceof SnowFall) {
                    this.config.effect.clear();
                }
                this.config.effect = new PopCornWrapper(this.config.elem);
                this.config.effect.setImage(this.tmp.image);
                this.config.effect.setSpeed(this.tmp.popcorn_speed);
                this.config.effect.setFlakeCount(this.tmp.count);
                $('input[name=effect_type_twinkle]').prop('disabled', true);
                break;
            case 4:
                var twinkle = $('input[name=effect_type_twinkle]').prop('checked');
                this.config.effect.setTwinkle(twinkle);
                break;
        }
    },
    wordCount: function () {
        var effect_name = $('input[name="effect_name"]');
        var len = effect_name.val().length;
        $('#word_count').text(len);
    },
    imageType: function (value) {
        if (value === 'default') {
            $('input[name=effect_image]').prop('disabled', false);
            if ($('input[name=effect_image]:checked').length === 0) {
                $('input[name=effect_image]:eq(0)').trigger('click');
            }
        } else if (value === 'custom') {
            $('input[name=effect_image]').prop('disabled', true);

            if (this.isCustomImageRegistered) {
                this.imageShape(this.config.effectImage);
            }
        }
    },
    imageShape: function (filename) {
        if (filename.match(/^custom/g)) {
            filename += '?_=' + Math.floor(Math.random() * 1000);
        }

        var image = this.config.commonImages + filename;
        this.tmp.image = image;

        if (this.config.effect) {
            this.config.effect.setImage(image);
        }
    },
    getSpeed: function (value, type) {
        value = Number(value);

        var snow = [1, 2, 3, 4, 5];
        var popcorn = [60, 50, 40, 30, 10];
        var speed;

        if (type === 'popcorn') {
            speed = popcorn[value - 1];
        } else {
            speed = snow[value - 1];
        }

        return speed;
    },
    setSpeed: function (value) {
        if (this.config.effect instanceof SnowFall) {
            this.tmp.speed = this.getSpeed(value);
            this.config.effect.setMinSpeed(this.tmp.speed);
            this.config.effect.setMaxSpeed(this.tmp.speed);
        } else {
            this.tmp.popcorn_speed = this.getSpeed(value, 'popcorn');
            this.config.effect.setSpeed(this.tmp.popcorn_speed);
        }
    },
    getFlakeCount: function (value) {
        value = Number(value);
        var flakeCount = [2, 4, 6, 8, 11];

        return flakeCount[value - 1];
    },
    setFlakeCount: function (value) {
        this.tmp.count = this.getFlakeCount(value);
        this.config.effect.setFlakeCount(this.tmp.count);
    },
    setOpacity: function (opacityRate) {
        this.tmp.opacity = opacityRate / 100;
        this.config.effect.setOpacity(opacityRate / 100);
    },
    validate: function () {
        if ($('input[name=effect_name]').val() === '') {
            alert('효과명을 입력해주세요.');
            return false;
        }

        var effectLimited = Number($('input[name=effect_limited]:checked').val());
        var startDate = $('input[name=effect_start_date]').val();
        var endDate = $('input[name=effect_end_date]').val();
        var start = moment(startDate + ' ' + $('input[name=effect_start_time]').val());
        var end = moment(endDate + ' ' + $('input[name=effect_end_time]').val());

        if (effectLimited === 1) {
            if (!start.isValid()) {
                alert('적용 시작 날짜를 입력해주세요.');
                return false;
            }
            if (!end.isValid()) {
                alert('적용 종료 날짜를 입력해주세요.');
                return false;
            }
            if (start.isAfter(end)) {
                if (start.isSame(end, 'day')) {
                    alert('종료 시간이 시작 시간보다 빠릅니다.');
                } else {
                    alert('종료 날짜가 시작 날짜보다 빠릅니다.');
                }
                return false;
            }
        }

        var image = document.getElementById('effect_image_custom');

        if (!this.isCustomImageRegistered && $('input[name=image_type][value=custom]:checked').length === 1 &&
            image.value === '') {
            alert('이미지를 등록해주세요.');
            return false;
        }

        if (!ImageModule.validateImage(image)) {
            return false;
        }

        if ($('input[name=effect_type]:checked').val() === '') {
            alert('효과 종류를 선택해주세요.');
            return false;
        }
        if ($('input[name=effect_speed]:checked').val() === '') {
            alert('효과 속도를 선택해주세요.');
            return false;
        }
        if ($('input[name=effect_amount]:checked').val() === '') {
            alert('효과 양을 선택해주세요.');
            return false;
        }
        if ($('input[name=effect_amount]:checked').val() === '') {
            alert('효과 투명도를 선택해주세요.');
            return false;
        }

        return true;
    },
    submit: function () {
        if (!this.validate()) {
            return false;
        }

        var validateForm = new FormData();
        validateForm.append('mode', 'validate');
        validateForm.append('effect_limited', $('input[name=effect_limited]:checked').val());
        validateForm.append('effect_start_date', $('input[name=effect_start_date]').val());
        validateForm.append('effect_end_date', $('input[name=effect_end_date]').val());

        if ($('#frmEffect input[name=sno]').length === 1) {
            validateForm.append('sno', $('#frmEffect input[name=sno]').val());
            $('#frmEffect').submit();
        } else {
            $.ajax({
                url: 'screen_effect_ps.php',
                data: validateForm,
                type: 'POST',
                processData: false,
                contentType: false,
                success: function (data) {
                    var count = data.result.count;

                    if (count >= 10) {
                        alert('화면 효과는 최대 10개까지만 등록이 가능합니다.');
                        location.href = './screen_effect_list.php';
                    } else {
                        $('#frmEffect').submit();
                    }
                },
                error: function () {
                    alert('처리 중에 오류가 발생하였습니다.');
                }
            });
        }
    },
    clearEffectDatetime: function () {
        $('input[name="effect_start_date"]').val('');
        $('input[name="effect_end_date"]').val('');
        $('input[name="effect_start_time"]').val('');
        $('input[name="effect_end_time"]').val('');
    },
    setDefaultDate: function() {
        $('input[name=effect_limited][value=1]').prop('checked', true);

        var startDate = $('input[name=effect_start_date]');
        var startTime = $('input[name=effect_start_time]');
        var endDate = $('input[name=effect_end_date]');
        var endTime = $('input[name=effect_end_time]');
        var today = moment();

        if (startDate.val() == '') {
            startDate.val(today.format('YYYY-MM-DD'));
        }
        if (startTime.val() == '') {
            startTime.val('00:00');
        }
        if (endDate.val() == '') {
            endDate.val(today.format('YYYY-MM-DD'));
        }
        if (endTime.val() == '') {
            endTime.val('23:59');
        }
    }
};
