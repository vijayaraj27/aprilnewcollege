@if($variant === 'simple')
{{-- Compact breadcrumb bar (department home, etc.) --}}
<section class="dept-simple-breadcrumb {{ $class }}">
    <div class="container">
        <div class="dept-simple-breadcrumb__row">
            @if($title)
            <h1 class="dept-simple-breadcrumb__title">{{ $title }}</h1>
            @endif
            @if(count($items) > 0)
            <nav aria-label="breadcrumb" class="dept-simple-breadcrumb__nav">
                <ol class="breadcrumb mb-0">
                    @foreach($items as $index => $item)
                        @if($index === count($items) - 1)
                            <li class="breadcrumb-item active" aria-current="page">{{ $item['title'] }}</li>
                        @else
                            <li class="breadcrumb-item"><a href="{{ $item['url'] ?? '#' }}">{{ $item['title'] }}</a></li>
                        @endif
                    @endforeach
                </ol>
            </nav>
            @endif
        </div>
    </div>
</section>
<style>
.dept-simple-breadcrumb {
    background: #125875;
    color: #fff;
    padding: 16px 0;
    border-bottom: 3px solid #f5874f;
}
.dept-simple-breadcrumb__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: nowrap;
}
.dept-simple-breadcrumb__title {
    color: #fff;
    font-size: 22px !important;
    font-weight: 700;
    margin: 0;
    line-height: 1.3;
    flex: 1 1 auto;
    min-width: 0;
}
.dept-simple-breadcrumb__nav {
    flex: 0 1 auto;
    text-align: right;
    min-width: 0;
}
.dept-simple-breadcrumb .breadcrumb {
    background: transparent !important;
    background-color: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
    display: flex !important;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: flex-end;
    list-style: none;
    white-space: nowrap;
}
.dept-simple-breadcrumb .breadcrumb-item,
.dept-simple-breadcrumb .breadcrumb-item.active,
.dept-simple-breadcrumb .breadcrumb > .active {
    display: inline-flex !important;
    align-items: center;
    float: none !important;
    color: rgba(255,255,255,.9) !important;
    font-size: 14px !important;
    font-weight: 500;
    line-height: 1.4 !important;
    padding: 0 !important;
    margin: 0 !important;
}
.dept-simple-breadcrumb .breadcrumb-item a {
    color: #fff !important;
    text-decoration: none;
    line-height: inherit;
}
.dept-simple-breadcrumb .breadcrumb-item a:hover {
    color: #f5874f !important;
    text-decoration: underline;
}
.dept-simple-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
    float: none !important;
    display: inline-block !important;
    position: static !important;
    color: rgba(255,255,255,.65) !important;
    content: "/" !important;
    padding: 0 8px !important;
    line-height: inherit;
}
@media (max-width: 767px) {
    .dept-simple-breadcrumb {
        padding: 12px 0;
    }
    .dept-simple-breadcrumb__row {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    .dept-simple-breadcrumb__title {
        font-size: 18px !important;
    }
    .dept-simple-breadcrumb__nav {
        text-align: left;
        width: 100%;
    }
    .dept-simple-breadcrumb .breadcrumb {
        justify-content: flex-start;
        flex-wrap: wrap;
        white-space: normal;
    }
}
</style>
@else
<!-- Breadcrumb Component -->
<section class="breadcrumb-area d-flex p-relative align-items-center {{ $class }}" style="background: {{ $background }}; background-color: #125875;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-xl-12 col-lg-12">
                <div class="breadcrumb-wrap text-left">
                    @if($title)
                    <div class="breadcrumb-title">
                        <h2>{{ $title }}</h2>
                    </div>
                    @endif
                </div>
            </div>
            @if(count($items) > 0)
            <div class="breadcrumb-wrap2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        @foreach($items as $index => $item)
                            @if($index === count($items) - 1)
                                <li class="breadcrumb-item active" aria-current="page">{{ $item['title'] }}</li>
                            @else
                                <li class="breadcrumb-item"><a href="{{ $item['url'] ?? '#' }}">{{ $item['title'] }}</a></li>
                            @endif
                        @endforeach
                    </ol>
                </nav>
            </div>
            @endif
        </div>
    </div>
</section>

<style>
/* Keep breadcrumb readable — never transparent text on white */
.breadcrumb-area {
    background-size: cover !important;
    background-repeat: no-repeat !important;
    background-position: center !important;
    height: 520px;
    min-height: 520px;
    color: #fff;
    padding: 50px 0 70px;
}

@media (min-width: 992px) and (max-width: 1200px) {
    .breadcrumb-area {
        height: 400px;
        min-height: 400px;
        padding: 40px 0 60px;
    }
}

@media (min-width: 768px) and (max-width: 991px) {
    .breadcrumb-area {
        height: 320px;
        min-height: 320px;
        padding: 36px 0 56px;
    }
}

@media (max-width: 767px) {
    .breadcrumb-area {
        height: 240px;
        min-height: 240px;
        padding: 30px 0 50px;
    }
}

.breadcrumb-area .breadcrumb-title h2 {
    color: #fff !important;
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0 auto 15px;
    line-height: 1.25;
    width: fit-content;
    max-width: 100%;
    background-color: rgba(0, 0, 0, 0.55) !important;
    padding: 10px 20px;
    border-radius: 10px;
}

.breadcrumb-area .breadcrumb-wrap2 {
    position: absolute;
    left: 0;
    width: 100%;
    text-align: center;
    bottom: -25px;
    z-index: 2;
}

.breadcrumb-area .breadcrumb {
    display: inline-block;
    background: #f5874f !important;
    background-color: #f5874f !important;
    border-radius: 30px;
    padding: 12px 28px !important;
    margin: 0;
    list-style: none;
}

.breadcrumb-area .breadcrumb-item,
.breadcrumb-area .breadcrumb-item.active,
.breadcrumb-area .breadcrumb > .active {
    color: #fff !important;
    font-weight: 600;
    font-size: 15px;
}

.breadcrumb-area .breadcrumb-item a {
    color: #fff !important;
    text-decoration: none;
    font-weight: 500;
    font-size: 15px;
}

.breadcrumb-area .breadcrumb-item a:hover {
    color: #fff7e8 !important;
    text-decoration: underline;
}

.breadcrumb-area .breadcrumb-item + .breadcrumb-item::before {
    color: rgba(255, 255, 255, 0.85) !important;
    content: "|" !important;
    padding-right: 12px;
    padding-left: 8px;
}

@media (max-width: 768px) {
    .breadcrumb-area .breadcrumb-title h2 {
        font-size: 1.6rem;
        padding: 8px 14px;
    }
    .breadcrumb-area .breadcrumb {
        padding: 10px 16px !important;
        max-width: 95%;
    }
}
</style>
@endif
