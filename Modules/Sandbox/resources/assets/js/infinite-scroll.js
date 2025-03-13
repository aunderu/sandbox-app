console.log('Infinite Scroll Ready!');

let page = 2;
let loading = false;
let hasMoreData = true; // เพิ่มตัวแปรเพื่อตรวจสอบว่ามีข้อมูลเพิ่มเติมหรือไม่

function handleScroll() {
    if (loading || !hasMoreData) return;

    if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500) {
        loading = true;
        document.getElementById('loading').style.display = 'block';

        fetch(`/load-more-innovations?page=${page}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';

                if (data.html) {
                    document.getElementById('innovation-container').insertAdjacentHTML('beforeend', data.html);
                    page = data.next_page ? data.next_page : page;
                    loading = false;

                    if (!data.next_page) {
                        hasMoreData = false;
                        window.removeEventListener('scroll', handleScroll);
                        document.getElementById('end-of-content').style.display = 'block';
                    }
                } else {
                    hasMoreData = false;
                    document.getElementById('end-of-content').style.display = 'block';
                }
            })
            .catch(() => {
                loading = false;
                document.getElementById('loading').style.display = 'none';
                document.getElementById('error-message').style.display = 'block';
            });
    }
}

window.addEventListener('scroll', handleScroll);