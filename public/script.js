{
    // ハンバーガーメニュー
    const h = document.getElementById("hamburger");
    h.addEventListener("click", () => {
        h.classList.toggle("on");
    });

    // ツイート取得の場合の期間指定の表示
    const objects = document.getElementsByName("object");
    const u = document.getElementById("using_term_area");
    for (object of objects) {
        object.addEventListener('change', () => {
            console.log(object);
        });
    }

}
