/*
Write a program to accept N integers from the user and store them in an array. Sort the array in
ascending order using Bubble sort. Then accept another number from the user, search whether that
number exists in the array using Binary Search. If it does, display its index and if it doesn’t, then print
that the number is not found in the array.
*/
#include <iostream>
using namespace std;

void swap(int *xp, int *yp)
{
	int temp = *xp;
	*xp = *yp;
	*yp = temp;
}

void bubble_sort(int *arr, int n)
{
int i, j;
bool s;
for (i = 0; i < n-1; i++)
{
	s = false;
	for (j = 0; j < n-i-1; j++)
	{
		if (arr[j] > arr[j+1])
		{
		swap(&arr[j], &arr[j+1]);
		s = true;
		}
	}

	if (s == false)
		break;
}
}


void print_Array(int *arr, int size)
{
	int i;
	for (i=0; i < size; i++)
		cout<<arr[i]<<" ";
	cout<<endl;
}
int binary_search(int *arr,int l,int r,int element)
{
if(l<=r){
        int m=l+(r-l)/2;
          if(arr[m]==element)
         return m;
         if(arr[m]>element)
         return binary_search(arr,l,m-1,element);

          return  binary_search(arr,m+1,r,element);
}
else
return -1;
}

int main()
{

	int size,element;
	cout<<"Enter the size of array : ";
	cin>>size;
	int *arr=new int[size];
	cout<<"Enter elements ->"<<endl;
	for(int i=0;i<size;i++)
	   cin>>arr[i];
	bubble_sort(arr,size);
	cout<<"Array after sorting ->"<<endl;
	print_Array(arr,size);
	cout<<"Enter Element to search ->"<<endl;
	cin>>element;
	int f=binary_search(arr,0,size-1,element);
	if(f>=0)
	cout<<element<<" found at "<<f+1<<" position"<<endl;
	else
	cout<<"ERROR! element not found"<<endl;
}
